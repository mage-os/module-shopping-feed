<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\ConditionalValue;

use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\Product\AbstractProduct;

/** Use Magento's product-condition semantics on the feed's store-scoped product. */
class Condition extends AbstractProduct
{
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
        if ($this->getOperator() === '<=>') {
            return $value === null;
        }
        if ($value === null) {
            return false;
        }
        if ($this->getInputType() === 'date') {
            $expected = strtotime((string)$this->getData('value'));
            $actual = strtotime((string)$value);
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
