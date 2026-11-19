define(['Magento_Ui/js/form/element/abstract', './conditional-value'], function (Element, conditionalValue) {
    'use strict';
    return Element.extend({
        defaults: {
            value: null,
            definition: {},
            conditional: {},
            conditionalEditor: null,
            ignoreTmpls: {definition: true, conditional: true},
            listens: {value: 'refreshEditor'}
        },
        initObservable: function () {
            this._super().observe('conditionalEditor');
            this.refreshEditor();
            return this;
        },
        refreshEditor: function () {
            if (this.writingConditions || !this.conditionalEditor) { return; }
            var self = this;
            var definition = Object.assign({}, this.definition, {conditions: (this.conditional || {}).conditions || {}});
            this.conditionalEditor(conditionalValue(definition, this.value(), function (value) {
                self.writingConditions = true;
                try { self.value(value); } finally { self.writingConditions = false; }
            }));
        }
    });
});
