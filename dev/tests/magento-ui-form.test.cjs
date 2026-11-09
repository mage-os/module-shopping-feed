const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Run explicitly with MAGENTO_ROOT: this exercises the installed framework's date conversion.
const framework = process.env.MAGENTO_ROOT;
if (!framework) { throw new Error('Set MAGENTO_ROOT to a Magento or Mage-OS checkout.'); }
const root = path.resolve(__dirname, '../..');
const ui = ['mage-os', 'magento'].map(vendor => path.join(framework, 'vendor', vendor, 'module-ui'))
    .find(directory => fs.existsSync(directory));
const moment = require(path.join(framework, 'lib/web/moment.js'));
const base = {extend: definition => definition};
function amd(file, dependencies) {
    let result;
    vm.runInNewContext(fs.readFileSync(file, 'utf8'), {
        define: (_, factory) => { result = factory(...dependencies); }
    });
    return result;
}
const utils = amd(path.join(framework, 'lib/web/mage/utils/misc.js'), [{}, {}, {}]);
const native = amd(path.join(ui, 'view/base/web/js/form/element/date.js'), [moment, utils, base]);
const promotion = amd(path.join(root, 'view/adminhtml/web/js/form/promotion-date.js'), [base]);
function observable(initial) {
    let value = initial;
    return function (next) { if (arguments.length) { value = next; } return value; };
}
function field(localeFormat, stored) {
    const result = Object.assign({}, native, promotion, {
        options: {dateFormat: localeFormat}, inputDateFormat: 'yyyy/MM/dd', outputDateFormat: 'yyyy/MM/dd',
        validationParams: {}, value: observable(stored), shiftedValue: observable('')
    });
    result._super = () => native.prepareDateTimeFormats.call(result);
    result.prepareDateTimeFormats();
    return result;
}
for (const [locale, format] of [['en_US', 'M/d/yy'], ['en_GB', 'dd/MM/y'], ['de_DE', 'dd.MM.y']]) {
    test(`all four promotion dates retain canonical storage through two saves in ${locale}`, () => {
        const dates = ['2026/10/01', '2027/04/02', '2026/10/02', '2027/04/01'];
        for (let stored of dates) {
            const original = stored;
            for (let save = 0; save < 2; save++) {
                const component = field(format, stored);
                component.onValueChange(stored);
                assert.notEqual(component.shiftedValue(), 'Invalid date');
                component.onShiftedValueChange(component.shiftedValue());
                stored = component.value();
                assert.equal(stored, original);
            }
        }
        const component = field(format, '');
        component.onShiftedValueChange(moment('2028-02-29').format(component.pickerDateTimeFormat));
        assert.equal(component.value(), '2028/02/29');
        component.onShiftedValueChange('');
        assert.equal(component.value(), '');
    });
}

const abstract = amd(path.join(ui, 'view/base/web/js/form/element/abstract.js'), [
    {}, {isEmpty: value => value === undefined || value === null || value === ''}, {}, base, {}
]);
const parameter = amd(path.join(root, 'view/adminhtml/web/js/form/parameter.js'), [base, options => options, text => text]);
test('directive parameter initialization preserves null instead of replacing generator defaults', () => {
    for (const original of [null, '', '0', 0, false, ['literal']]) {
        const component = Object.assign({}, abstract, parameter, {
            value: observable(original), default: '', _super: () => abstract.getInitialValue.call(component)
        });
        assert.equal(component.getInitialValue(), original);
    }
});

test('framework value links preserve null parameters before form initialization', () => {
    const underscore = require(path.join(framework, 'lib/web/underscore.js'));
    for (const stored of [null, '', '0', 0, false, ['literal']]) {
        const provider = {name: 'provider', value: stored, get() { return this.value; },
            set(key, value) { this.value = value; }, on() {}};
        const links = amd(path.join(ui, 'view/base/web/js/lib/core/element/links.js'), [
            {}, underscore, {copy: value => value}, {get: (name, callback) => callback(provider)}
        ]);
        const field = Object.assign({}, abstract, parameter, links, {
            name: 'parameter', maps: {imports: {}, exports: {}},
            value: observable({...abstract.defaults, ...parameter.defaults}.value),
            get() { return this.value(); }, set(key, value) { this.value(value); }, on() {}
        });
        field.setLinks({value: 'provider:param'}, 'imports');
        field.setLinks({value: 'provider:param'}, 'exports');
        assert.equal(provider.value, stored);
        assert.equal(field.getInitialValue(), stored);
    }
});

const ko = require(path.join(framework, 'lib/web/knockoutjs/knockout.js'));
const preserveConditional = amd(path.join(root, 'view/adminhtml/web/js/form/preserve-options.js'), []);
const conditionalEditor = amd(path.join(root, 'view/adminhtml/web/js/form/conditional-value.js'),
    [ko, preserveConditional, text => text]);
const conditionalDefinitions = {
    conditions: {
        price: {label: 'Price', input: 'string', operators: [{value: '>', label: 'greater than'}]},
        attribute_set_id: {label: 'Attribute Set', input: 'select', operators: [{value: '==', label: 'is'}],
            options: [{value: '4', label: 'Clothing'}]}
    },
    attributes: [{value: 'price', label: 'Price'}, {value: 'manufacturer', label: 'Brand'}]
};

test('conditional editor persists all/any, conditions, both outputs and literal text through reload', () => {
    let saved;
    const editor = conditionalEditor(conditionalDefinitions, '', value => { saved = value; });
    editor.addCondition();
    const row = editor.conditions()[0];
    row.attribute('attribute_set_id');
    row.value('4');
    assert.equal(row.useSelect(), true);
    editor.ifTrue.type('attribute');
    editor.ifTrue.attribute('manufacturer');
    editor.ifFalse.value('0 ${literal} <script>');
    editor.aggregator('any');
    const before = JSON.stringify(saved);
    const reloaded = conditionalEditor(conditionalDefinitions, JSON.parse(before), value => { saved = value; });
    assert.equal(reloaded.ifFalse.value(), '0 ${literal} <script>');
    assert.equal(reloaded.conditions()[0].value(), '4');
    assert.equal(JSON.stringify(saved), before);
    reloaded.ifTrue.type('percent');
    reloaded.ifTrue.attribute('price');
    reloaded.ifTrue.percent('-10');
    assert.equal(saved.if_true.percent, '-10');
    reloaded.removeCondition(reloaded.conditions()[0]);
    assert.equal(saved.conditions.length, 0);
});

test('conditional editor does not mutate shared defaults or other column parameters', () => {
    let saved;
    const first = conditionalEditor(conditionalDefinitions, '', value => { saved = value; });
    const second = conditionalEditor(conditionalDefinitions, '', () => {});
    first.addCondition();
    first.conditions()[0].attribute('price');
    first.conditions()[0].value('0');
    assert.equal(saved.conditions[0].operator, '>');
    assert.equal(saved.conditions[0].value, '0');
    assert.equal(second.conditions().length, 0);
    assert.equal(conditionalDefinitions.conditions.price.operators[0].value, '>');
});

test('late provider imports refresh conditional controls without resetting a row during typing', () => {
    const component = amd(path.join(root, 'view/adminhtml/web/js/form/parameter.js'),
        [base, preserveConditional, text => text, conditionalEditor]);
    const field = Object.assign({}, component, {
        definitions: {conditional: {...conditionalDefinitions, kind: 'conditional'}},
        value: ko.observable(null), kind: ko.observable('none'), parameterLabel: ko.observable(''),
        parameterNotice: ko.observable(''), parameterOptions: ko.observableArray([]),
        conditionalEditor: ko.observable(null)
    });
    field.value.subscribe(() => field.refreshConditionalEditor());
    field.changeAttribute('conditional');
    field.value({aggregator: 'any', conditions: [{attribute: 'price', operator: '>', value: '25'}],
        if_true: {type: 'static', value: 'high'}, if_false: {type: 'static', value: 'low'}});
    const editor = field.conditionalEditor();
    assert.equal(editor.conditions()[0].value(), '25');
    editor.conditions()[0].value('30');
    assert.equal(field.conditionalEditor(), editor);
    assert.equal(field.value().conditions[0].value, '30');
});
