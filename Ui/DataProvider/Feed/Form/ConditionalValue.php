<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form;

use MageOS\ShoppingFeed\Model\ConditionalValue\ConditionFactory;

/** Native Magento condition choices, rendered by the active Knockout parameter editor. */
class ConditionalValue
{
    /**
     * Constructor.
     *
     * @param ConditionFactory $conditionFactory
     */
    public function __construct(private ConditionFactory $conditionFactory)
    {
    }

    /**
     * Supply attribute-specific operators and option IDs, including attribute sets.
     *
     * @return array<string, array<string, mixed>>
     */
    public function get(): array
    {
        $result = [];
        $prototype = $this->conditionFactory->create();
        foreach ($prototype->getAttributeOption() as $code => $label) {
            $condition = $this->conditionFactory->create();
            $condition->setAttribute($code);
            $result[$code] = [
                'label' => $label,
                'input' => $condition->getInputType(),
                'operators' => $condition->getOperatorSelectOptions(),
                'options' => $condition->getValueSelectOptions()
            ];
        }
        return $result;
    }
}
