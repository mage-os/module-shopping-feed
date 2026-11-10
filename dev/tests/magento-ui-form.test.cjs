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

const dependentInput = amd(path.join(root, 'view/adminhtml/web/js/form/dependent-input.js'), [base]);
const nativeTextarea = amd(path.join(ui, 'view/base/web/js/form/element/textarea.js'), [base]);
const dependentTextarea = amd(path.join(root, 'view/adminhtml/web/js/form/dependent-textarea.js'), [{
    extend: definition => ({...nativeTextarea, ...definition,
        defaults: {...nativeTextarea.defaults, ...definition.defaults}})
}]);
test('XML wrappers use a textarea template that preserves multiline values', () => {
    assert.equal(dependentTextarea.defaults.elementTmpl, 'ui/form/element/textarea');
    assert.equal(dependentTextarea.defaults.cols, 15);
    assert.equal(dependentTextarea.defaults.rows, 2);
    const template = fs.readFileSync(path.join(ui, 'view/base/web/templates/form/element/textarea.html'), 'utf8');
    assert.match(template, /<textarea/);
    assert.match(template, /value: value/);
});
test('Other Delimiter follows both its delimiter selection and the output format visibility', () => {
    const component = Object.assign({}, dependentInput, {
        showWhen: 'other', parentVisible: true, visible: observable(false)
    });
    component.updateVisibility('other');
    assert.equal(component.visible(), true);
    component.updateParentVisibility(false);
    assert.equal(component.visible(), false);
    component.updateVisibility('other');
    assert.equal(component.visible(), false);
    component.updateParentVisibility(true);
    assert.equal(component.visible(), true);
    component.updateVisibility(',');
    assert.equal(component.visible(), false);
});
