define(['Magento_Ui/js/form/element/abstract'], function (Element) {
    'use strict';
    return Element.extend({
        defaults: {
            parentVisible: true,
            listens: {dependency: 'updateVisibility', parentVisible: 'updateParentVisibility'}
        },
        updateVisibility: function (value) {
            this.dependencyValue = value;
            this.visible(this.parentVisible && String(value) === this.showWhen);
        },
        updateParentVisibility: function (value) {
            this.parentVisible = value;
            this.updateVisibility(this.dependencyValue);
        }
    });
});
