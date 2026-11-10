define(['Magento_Ui/js/form/element/abstract', './conditional-value'], function (Element, conditionalValue) {
    'use strict';
    return Element.extend({
        defaults: {
            value: null,
            definition: {},
            conditionalEditor: null,
            ignoreTmpls: {definition: true},
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
            this.conditionalEditor(conditionalValue(this.definition, this.value(), function (value) {
                self.writingConditions = true;
                try { self.value(value); } finally { self.writingConditions = false; }
            }));
        }
    });
});
