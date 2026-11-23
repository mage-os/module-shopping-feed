define([
    'Magento_Ui/js/form/element/abstract', './preserve-options', 'mage/translate', './conditional-value', 'uiRegistry'
], function (Element, preserve, $t, conditionalValue, registry) {
    'use strict';
    return Element.extend({
        defaults: {
            // Initial value links must not replace a stored null with the base field's empty string.
            value: null,
            definitions: {}, conditional: {}, kind: 'none', parameterLabel: '', parameterNotice: '', parameterOptions: [], conditionalEditor: null,
            ignoreTmpls: {definitions: true, conditional: true},
            listens: {attribute: 'changeAttribute', value: 'refreshConditionalEditor', conditional: 'refreshConditionalEditor'}
        },
        initObservable: function () {
            this._super().observe('kind parameterLabel parameterNotice parameterOptions conditionalEditor');
            this.linkConditionalSource();
            return this;
        },
        linkConditionalSource: function () {
            // Condition attributes and options are held once, by the product selection field. Rows read
            // them by reference: an import link would give every row its own copy (about 60 KB each).
            if (!registry || !this.ns) {
                return;
            }
            var self = this;
            registry.get(this.ns + '.' + this.ns + '.filters.filters_product_conditions', function (source) {
                self.conditionalSource = source;
                self.refreshConditionalEditor();
            });
        },
        normalizeData: function (value) {
            return value === undefined ? '' : value;
        },
        getInitialValue: function () {
            var value = this.value();
            // Null invokes generator defaults; an explicit empty string does not.
            return value === undefined ? this.normalizeData(this.default) : value;
        },
        refreshConditionalEditor: function () {
            if (this.writingConditionalValue || !this.conditionalEditor || this.kind() !== 'conditional') {
                return;
            }
            var self = this,
                shared = (this.conditionalSource && this.conditionalSource.conditional) || this.conditional || {},
                definition = Object.assign({}, this.definitions[this.previousAttribute], shared);
            this.conditionalEditor(conditionalValue(definition, this.value(), function (value) {
                self.writingConditionalValue = true;
                try {
                    self.value(value);
                } finally {
                    self.writingConditionalValue = false;
                }
            }));
        },
        revealSelection: function (element) {
            // The compact list shows three options; start it at the first selected one.
            window.setTimeout(function () {
                var selected = element.querySelector('option:checked');
                if (selected && element.options.length) {
                    element.scrollTop = selected.index * (element.scrollHeight / element.options.length);
                }
            }, 0);
        },
        changeAttribute: function (attribute) {
            var known = Object.prototype.hasOwnProperty.call(this.definitions, attribute),
                definition = known ? this.definitions[attribute] : {kind: 'none'},
                value = this.value();
            if (this.previousAttribute !== undefined && this.previousAttribute !== attribute) {
                value = definition.default === undefined ? '' : definition.default;
                if (definition.kind === 'multiselect') {
                    value = Array.isArray(value) ? value : (value ? String(value).split(',') : []);
                }
                this.value(value);
            }
            if (!known && value !== '' && value !== null && value !== undefined) {
                definition = {
                    kind: 'unsupported',
                    notice: $t('This saved parameter is preserved. Its extension needs a UI component parameter definition to edit it.')
                };
            }
            this.previousAttribute = attribute;
            this.parameterOptions(preserve(definition.options || [], value));
            this.parameterLabel(definition.label || '');
            this.parameterNotice(definition.notice || '');
            // Unknown structured parameters remain untouched instead of being coerced to text.
            this.kind(definition.kind);
            if (definition.kind === 'conditional') {
                this.refreshConditionalEditor();
            } else if (this.conditionalEditor) {
                this.conditionalEditor(null);
            }
        }
    });
});
