/* Browser regression: run against a disposable installed Magento Admin static deployment. */
require([
    'ko', 'uiRegistry', 'Magento_Ui/js/form/provider', 'MageOS_ShoppingFeed/js/form/parameter',
    'Magento_Ui/js/lib/knockout/template/engine', 'Magento_Ui/js/lib/knockout/bootstrap'
], function (ko, registry, Provider, Parameter, engine) {
    'use strict';

    var result = document.getElementById('result'), host = document.getElementById('fixture'),
        errors = [], checks = [], field, provider,
        definitions = {
            directive_static_value: {kind: 'input', label: 'Value', default: ''},
            directive_conditional_value: {
                kind: 'conditional', label: 'Conditional Value', default: '',
                conditions: {price: {label: 'Price', input: 'string',
                    operators: [{value: '>', label: 'greater than'}], options: []}},
                attributes: [{value: 'price', label: 'Price'}]
            }
        };

    window.addEventListener('error', function (event) { errors.push(event.message); });
    window.addEventListener('unhandledrejection', function (event) { errors.push(String(event.reason)); });
    ko.setTemplateEngine(engine);
    ko.cleanNode(host);
    provider = new Provider({name: 'conditional_switch_provider', data: {param: 'saved static text'}});
    registry.set(provider.name, provider);

    function check(ok, message) {
        if (!ok) { throw new Error(message); }
        checks.push(message);
    }
    function waitFor(predicate) {
        var started = Date.now();
        return new Promise(function (resolve, reject) {
            function poll() {
                if (errors.length) { reject(new Error(errors.join('\n'))); return; }
                if (predicate()) { resolve(); return; }
                if (Date.now() - started > 10000) { reject(new Error('Timed out waiting for rendered controls')); return; }
                setTimeout(poll, 20);
            }
            poll();
        });
    }
    function mount(attribute, value) {
        provider.set('data.param', value);
        field = new Parameter({name: 'conditional_switch_parameter', provider: provider.name,
            dataScope: 'data.param', definitions: definitions, value: value, attribute: attribute});
        field.changeAttribute(attribute);
        host.setAttribute('data-bind', "template: {name: 'MageOS_ShoppingFeed/form/parameter', data: $data}");
        ko.applyBindings(field, host);
    }
    function unmount() {
        ko.cleanNode(host);
        host.textContent = '';
        field.destroy();
    }
    function input(element, value) {
        element.value = value;
        element.dispatchEvent(new Event(element.tagName === 'SELECT' ? 'change' : 'input', {bubbles: true}));
    }
    function outputBranches() {
        return Array.from(host.querySelectorAll('fieldset')).filter(function (node) {
            var legend = node.querySelector(':scope > legend');
            return legend && ['If matching', 'Otherwise'].indexOf(legend.textContent) !== -1;
        });
    }
    async function run() {
        mount('directive_static_value', 'saved static text');
        await waitFor(function () { return host.querySelector('input'); });
        check(host.querySelector('input').value === 'saved static text', 'Existing Static Value mapping renders');
        // Crucially, the parameter template is already bound when the source changes.
        // Before the fix this synchronously throws in RemoteTemplateEngine.makeTemplateSource.
        field.changeAttribute('directive_conditional_value');
        await waitFor(function () { return outputBranches().length === 2 && outputBranches().every(function (node) { return node.querySelector('input'); }); });
        check(host.querySelector('button.action-add') !== null, 'Switch renders condition and both output controls');
        host.querySelector('button.action-add').click();
        var condition = host.querySelector('fieldset fieldset'), branches = outputBranches();
        input(condition.querySelector('select'), 'price');
        input(condition.querySelectorAll('select')[1], '>');
        input(condition.querySelector('input'), '75');
        input(branches[0].querySelector('input'), 'switch-match');
        input(branches[1].querySelector('input'), 'switch-fallback');
        var saved = JSON.parse(JSON.stringify(provider.get('data.param')));
        check(saved.conditions[0].attribute === 'price' && saved.conditions[0].value === '75',
            'Condition changes reach the real form provider');
        check(saved.if_true.value === 'switch-match' && saved.if_false.value === 'switch-fallback',
            'Both branches reach the real form provider');
        unmount();
        mount('directive_conditional_value', saved);
        await waitFor(function () { return outputBranches().length === 2 && outputBranches().every(function (node) { return node.querySelector('input'); }); });
        branches = outputBranches();
        condition = host.querySelector('fieldset fieldset');
        check(condition.querySelector('select').value === 'price' &&
            condition.querySelectorAll('select')[1].value === '>' &&
            condition.querySelector('input').value === '75', 'Reopening renders the saved condition');
        check(branches[0].querySelector('input').value === 'switch-match' &&
            branches[1].querySelector('input').value === 'switch-fallback', 'Reopening renders both saved branches');
        field.changeAttribute('directive_static_value');
        await waitFor(function () { return host.querySelectorAll('fieldset').length === 0; });
        field.changeAttribute('directive_conditional_value');
        await waitFor(function () { return outputBranches().length === 2 && outputBranches().every(function (node) { return node.querySelector('input'); }); });
        check(field.conditionalEditor().conditions().length === 0, 'Switching back uses a fresh editor with cached templates');
        check(errors.length === 0, 'No browser binding errors');
        result.textContent = 'PASS: ' + checks.length + ' checks\n' + checks.join('\n');
    }
    run().catch(function (error) { result.textContent = 'FAIL: ' + error.stack + '\n' + checks.join('\n'); });
});
