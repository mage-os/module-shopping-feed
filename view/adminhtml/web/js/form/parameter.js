define([
    'Magento_Ui/js/form/element/abstract', './preserve-options', 'mage/translate', './conditional-value'
], function (Element, preserve, $t, conditionalValue) {
    'use strict';
    return Element.extend({
        defaults: {
            // Initial value links must not replace a stored null with the base field's empty string.
            value: null,
            definitions: {}, kind: 'none', parameterLabel: '', parameterNotice: '', parameterOptions: [], conditionalEditor: null,
            ignoreTmpls: {definitions: true}, listens: {attribute: 'changeAttribute', value: 'refreshConditionalEditor'}
        },
        initObservable: function () {
            this._super().observe('kind parameterLabel parameterNotice parameterOptions conditionalEditor');
            return this;
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
            var self = this, definition = this.definitions[this.previousAttribute];
            this.conditionalEditor(conditionalValue(definition, this.value(), function (value) {
                self.writingConditionalValue = true;
                try {
                    self.value(value);
                } finally {
                    self.writingConditionalValue = false;
                }
            }));
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
