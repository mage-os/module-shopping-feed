define(['Magento_Ui/js/form/element/textarea'], function (Textarea) {
    'use strict';
    return Textarea.extend({
        defaults: {listens: {dependency: 'updateVisibility'}},
        updateVisibility: function (value) { this.visible(String(value) === this.showWhen); }
    });
});
