<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\ConditionalValue;

use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\Product\AbstractProduct;

/** Use Magento's product-condition semantics on the feed's store-scoped product. */
class Condition extends AbstractProduct
{
    /**
     * List all scalar product attributes, including those not enabled for promotions.
     *
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $attributes = [];
        foreach ($this->_productResource->loadAllAttributes()->getAttributesByCode() as $attribute) {
            if ($this->supportsAttribute($attribute)) {
                $attributes[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
            }
        }
        $this->_addSpecialAttributes($attributes);
        $attributes['has_image'] = __('Has base image');
        asort($attributes);
        $this->setAttributeOption($attributes);
        return $this;
    }

    /**
     * Share the editor's scalar attribute contract with save/import validation.
     *
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute
     * @return bool
     */
    public function supportsAttribute(
        \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute
    ): bool {
        return (bool)$attribute->getFrontendLabel()
            && $attribute->getFrontendInput() !== 'gallery'
            && !in_array($attribute->getAttributeCode(), [
                'type_id', 'tier_price', 'quantity_and_stock_status', 'media_gallery'
            ], true);
    }

    /**
     * Get the input type used to select native rule operators.
     *
     * @return string
     */
    public function getInputType()
    {
        if ($this->getAttribute() === 'has_image') {
            return 'boolean';
        }
        if (!in_array($this->getAttribute(), ['attribute_set_id', 'category_ids'], true)
            && $this->getAttributeObject()->getFrontendInput() === 'datetime'
        ) {
            return 'date';
        }
        return parent::getInputType();
    }

    /**
     * Get attribute options, including the virtual image-presence field.
     *
     * @return array<array<string, mixed>>
     */
    public function getValueSelectOptions()
    {
        return $this->getAttribute() === 'has_image'
            ? [['value' => '1', 'label' => __('Yes')], ['value' => '0', 'label' => __('No')]]
            : parent::getValueSelectOptions();
    }

    /**
     * Get native operators plus bounded SKU wildcards.
     *
     * @return array<array<string, mixed>>
     */
    public function getOperatorSelectOptions()
    {
        if ($this->getAttribute() === 'has_image') {
            return [['value' => '==', 'label' => __('is')], ['value' => '!=', 'label' => __('is not')]];
        }
        $options = parent::getOperatorSelectOptions();
        if ($this->getAttribute() === 'sku') {
            $options[] = ['value' => 'glob', 'label' => __('matches wildcard')];
            $options[] = ['value' => '!glob', 'label' => __('does not match wildcard')];
        }
        return $options;
    }

    /**
     * Compare already-loaded store data; never reload a missing attribute in the default store.
     *
     * @param AbstractModel $model
     * @return bool
     */
    public function validate(AbstractModel $model)
    {
        $code = $this->getAttribute();
        $value = $code === 'category_ids' ? $model->getCategoryIds() : $model->getData($code);
        if ($code === 'has_image') {
            $image = $model->getData('image');
            $value = is_string($image) && trim($image) !== '' && $image !== 'no_selection' ? '1' : '0';
        }
        if ($code === 'sku' && in_array($this->getOperator(), ['glob', '!glob'], true)) {
            if ($value === null) {
                return false;
            }
            $pattern = str_replace(['\\*', '\\?'], ['.*', '.'], preg_quote((string)$this->getValue(), '~'));
            $matches = preg_match('~^' . $pattern . '$~Dsu', (string)$value);
            // A regex engine limit must never turn a failed match into an inclusion.
            return $matches !== false && ($this->getOperator() === 'glob' ? $matches === 1 : $matches === 0);
        }
        if (is_array($value) && count(array_filter($value, 'is_scalar')) !== count($value)) {
            return false;
        }
        if ($this->getOperator() === '<=>') {
            return $value === null;
        }
        if ($value === null) {
            return false;
        }
        if ($this->getInputType() === 'date') {
            $expected = strtotime((string)$this->getData('value'));
            // Both Date and Date and Time attributes compare their stored calendar day.
            $actual = strtotime(substr((string)$value, 0, 10));
            if ($expected === false || $actual === false) {
                return false;
            }
            $this->setValueParsed($expected);
            $value = $actual;
        } elseif ($this->getInputType() === 'multiselect' && !is_array($value)) {
            $value = $value === '' ? [] : explode(',', (string)$value);
        }
        return (bool)$this->validateAttribute($value);
    }
}
