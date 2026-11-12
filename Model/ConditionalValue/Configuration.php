<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\ConditionalValue;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;

/** Validate both saved and imported directive parameters without instantiating user-supplied classes. */
class Configuration
{
    public const DIRECTIVE = 'directive_conditional_value';

    /**
     * Constructor.
     *
     * @param Config $eavConfig
     * @param ConditionFactory $conditionFactory
     */
    public function __construct(private Config $eavConfig, private ConditionFactory $conditionFactory)
    {
    }

    /**
     * Validate a complete if/else expression before saving or generating it.
     *
     * @param mixed $value
     * @return array<string, mixed>
     * @throws LocalizedException
     */
    public function validate(mixed $value): array
    {
        $value = $this->validateConditions($value);
        foreach (['if_true', 'if_false'] as $key) {
            $this->validateOutput($value[$key] ?? null);
        }
        return $value;
    }

    /**
     * Validate the shared predicate independently of if/else output branches.
     *
     * @param mixed $value
     * @return array<string, mixed>
     * @throws LocalizedException
     */
    public function validateConditions(mixed $value): array
    {
        if (!is_array($value) || !in_array($value['aggregator'] ?? null, ['all', 'any'], true)
            || !is_array($value['conditions'] ?? null) || !array_is_list($value['conditions'])
            || count($value['conditions']) < 1 || count($value['conditions']) > 50
        ) {
            throw new LocalizedException(__('A rule requires a list of between 1 and 50 conditions and All or Any.'));
        }
        foreach ($value['conditions'] as $row) {
            if (!is_array($row) || !is_string($row['attribute'] ?? null)
                || !is_string($row['operator'] ?? null) || !is_scalar($row['value'] ?? null)
            ) {
                throw new LocalizedException(__('Invalid rule condition.'));
            }
            if (!in_array($row['scope'] ?? 'product', ['product', 'parent'], true)) {
                throw new LocalizedException(__('Invalid condition scope.'));
            }
            $condition = $this->conditionFactory->create();
            if ($row['attribute'] !== 'has_image') {
                $this->validateAttribute($row['attribute']);
                if (!in_array($row['attribute'], ['attribute_set_id', 'category_ids'], true)) {
                    $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $row['attribute']);
                    if (!$condition->supportsAttribute($attribute)) {
                        throw new LocalizedException(__('Unsupported condition attribute: %1.', $row['attribute']));
                    }
                }
            }
            if ($row['attribute'] === 'has_image' && !in_array((string)$row['value'], ['0', '1'], true)) {
                throw new LocalizedException(__('Has base image requires Yes or No.'));
            }
            if (strlen((string)$row['value']) > 4096) {
                throw new LocalizedException(__('Condition values must not exceed 4096 bytes.'));
            }
            $condition->setAttribute($row['attribute']);
            $operators = array_column($condition->getOperatorSelectOptions(), 'value');
            if (!in_array($row['operator'], $operators, true)) {
                throw new LocalizedException(__('Invalid condition operator for %1.', $row['attribute']));
            }
            if (in_array($condition->getInputType(), ['select', 'multiselect', 'boolean'], true)
                && in_array($row['operator'], ['==', '!=', '()', '!()', '{}', '!{}'], true)
                && $row['attribute'] !== 'category_ids'
            ) {
                $options = $condition->getValueSelectOptions();
                $allowed = $this->optionValues($options);
                $values = in_array($row['operator'], ['()', '!()', '{}', '!{}'], true)
                    ? array_map('trim', explode(',', (string)$row['value'])) : [(string)$row['value']];
                if (array_diff($values, $allowed)) {
                    throw new LocalizedException(__(
                        'Use option values for condition attribute: %1.',
                        $row['attribute']
                    ));
                }
            }
            if ($condition->getInputType() === 'date' && $row['operator'] !== '<=>'
                && (!is_string($row['value']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $row['value'])
                    || date('Y-m-d', strtotime($row['value']) ?: 0) !== $row['value'])
            ) {
                throw new LocalizedException(__('Condition dates must use YYYY-MM-DD.'));
            }
        }
        return $value;
    }

    /**
     * Flatten native option groups without accepting labels as stored option values.
     *
     * @param array $options
     * @phpstan-param array<array<string, mixed>> $options
     * @return list<string>
     */
    private function optionValues(array $options): array
    {
        $values = [];
        foreach ($options as $option) {
            if (is_array($option['value'] ?? null)) {
                foreach ($this->optionValues($option['value']) as $nestedValue) {
                    $values[] = $nestedValue;
                }
            } elseif (isset($option['value']) && (string)$option['value'] !== '') {
                $values[] = (string)$option['value'];
            }
        }
        return $values;
    }

    /**
     * Validate an output branch, including one that does not currently match any products.
     *
     * @param mixed $output
     * @return void
     * @throws LocalizedException
     */
    private function validateOutput(mixed $output): void
    {
        if (!is_array($output) || !in_array($output['type'] ?? null, ['static', 'attribute', 'percent'], true)) {
            throw new LocalizedException(__('Invalid Conditional Value output type.'));
        }
        if ($output['type'] === 'static') {
            if (!is_scalar($output['value'] ?? null)) {
                throw new LocalizedException(__('Conditional Value fixed text must be a scalar value.'));
            }
            return;
        }
        $this->validateAttribute($output['attribute'] ?? null);
        if ($output['type'] === 'percent'
            && (!is_scalar($output['percent'] ?? null) || !is_numeric($output['percent'])
                || !is_finite((float)$output['percent']) || (float)$output['percent'] < -100
                || (float)$output['percent'] > 100000)
        ) {
            throw new LocalizedException(__('Conditional Value percentage must be between -100 and 100000.'));
        }
    }

    /**
     * Accept existing product attributes and Magento's special condition attributes only.
     *
     * @param mixed $code
     * @return void
     * @throws LocalizedException
     */
    private function validateAttribute(mixed $code): void
    {
        if (!is_string($code) || !preg_match('/^[a-z][a-z0-9_]*$/D', $code)) {
            throw new LocalizedException(__('Invalid product attribute.'));
        }
        if (!in_array($code, ['attribute_set_id', 'category_ids'], true)
            && !$this->eavConfig->getAttribute(Product::ENTITY, $code)->getId()
        ) {
            throw new LocalizedException(__('Unknown product attribute: %1.', $code));
        }
    }
}
