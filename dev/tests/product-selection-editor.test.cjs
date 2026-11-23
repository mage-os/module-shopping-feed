const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const framework = process.env.MAGENTO_ROOT;
if (!framework) { throw new Error('Set MAGENTO_ROOT to a Magento or Mage-OS checkout.'); }
const ko = require(path.join(framework, 'lib/web/knockoutjs/knockout.js'));
const root = path.resolve(__dirname, '../..');
function amd(name, dependencies) {
    let result;
    vm.runInNewContext(fs.readFileSync(path.join(root, 'view/adminhtml/web/js/form', name + '.js'), 'utf8'), {
        define: (_, factory) => { result = factory(...dependencies); }
    });
    return result;
}
const preserve = amd('preserve-options', []);
const editor = amd('conditional-value', [ko, preserve, text => text]);
const plain = value => JSON.parse(JSON.stringify(value));
const definition = {selection: true, conditions: {
    sku: {label: 'SKU', input: 'string', operators: [{value: 'glob', label: 'matches wildcard'}], options: []},
    has_image: {label: 'Has base image', input: 'boolean', operators: [{value: '==', label: 'is'}],
        options: [{value: '1', label: 'Yes'}, {value: '0', label: 'No'}]}
}};

test('selection initialization preserves stored values without writing defaults', () => {
    for (const initial of [null, '', [], {aggregator: 'all', conditions: []}]) {
        let writes = 0;
        const state = editor(definition, initial, () => writes++);
        assert.equal(state.conditions().length, 0);
        assert.equal(writes, 0);
    }
});

test('selection saves product and parent conditions without if/else output fields', () => {
    let saved;
    const state = editor(definition, null, value => { saved = plain(value); });
    state.addCondition();
    const row = state.conditions()[0];
    row.attribute('sku');
    row.scope('parent');
    row.value('SHIRT-*${literal}');
    assert.deepEqual(saved, {aggregator: 'all', conditions: [
        {attribute: 'sku', operator: 'glob', value: 'SHIRT-*${literal}', scope: 'parent'}
    ]});
    const reopened = editor(definition, saved, value => { saved = plain(value); });
    assert.equal(reopened.conditions()[0].scope(), 'parent');
    assert.equal(reopened.conditions()[0].value(), 'SHIRT-*${literal}');
    reopened.aggregator('any');
    reopened.removeCondition(reopened.conditions()[0]);
    assert.deepEqual(saved, {aggregator: 'any', conditions: []});
});

test('image Yes/No preserves zero and switching attributes clears obsolete operators', () => {
    let saved;
    const state = editor(definition, null, value => { saved = plain(value); });
    state.addCondition();
    const row = state.conditions()[0];
    row.attribute('sku'); row.value('sku*');
    row.attribute('has_image'); row.value('0');
    assert.equal(row.useSelect(), true);
    assert.equal(saved.conditions[0].operator, '==');
    assert.equal(saved.conditions[0].value, '0');
});

test('SHO-2 still saves both output branches and its existing product condition shape', () => {
    let saved;
    const state = editor({...definition, selection: false}, {
        aggregator: 'all', conditions: [{attribute: 'sku', operator: 'glob', value: '*'}],
        if_true: {type: 'static', value: '0'}, if_false: {type: 'static', value: 'fallback'}
    }, value => { saved = plain(value); });
    state.conditions()[0].value('SHIRT-*');
    assert.equal(saved.if_true.value, '0');
    assert.equal(saved.if_false.value, 'fallback');
    assert.equal('scope' in saved.conditions[0], false);
});

test('unknown saved choices survive and condition limit is enforced', () => {
    const state = editor(definition, {aggregator: 'all', conditions: [
        {attribute: 'deleted_attribute', operator: 'unknown', value: '0', scope: 'parent'}
    ]}, () => {});
    assert.equal(state.conditions()[0].attributes.at(-1).value, 'deleted_attribute');
    assert.equal(state.conditions()[0].operators().at(-1).value, 'unknown');
    for (let i = 0; i < 60; i++) { state.addCondition(); }
    assert.equal(state.conditions().length, 50);
});


test('legacy keyed conditions reopen without loss and save as a list after editing', () => {
    let saved;
    const state = editor(definition, {aggregator: 'all', conditions: {
        first: {attribute: 'sku', operator: 'glob', value: 'old*', scope: 'parent'}
    }}, value => { saved = plain(value); });
    assert.equal(state.conditions()[0].value(), 'old*');
    assert.equal(saved, undefined);
    state.conditions()[0].value('new*');
    assert.equal(Array.isArray(saved.conditions), true);
    assert.equal(saved.conditions[0].scope, 'parent');
});
