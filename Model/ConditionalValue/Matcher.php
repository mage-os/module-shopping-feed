<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\ConditionalValue;

use Magento\Catalog\Model\Product;

/** Shared, store-scoped predicate engine for column values and product selection. */
class Matcher
{
    /** @var array<string, array{all: bool, conditions: list<array{scope: string, condition: Condition}>}> */
    private array $compiled = [];

    /**
     * Constructor.
     *
     * @param Configuration $configuration
     * @param ConditionFactory $conditionFactory
     */
    public function __construct(private Configuration $configuration, private ConditionFactory $conditionFactory)
    {
    }

    /**
     * Compare loaded product data. Missing parents never satisfy a parent condition.
     *
     * @param array<string,mixed> $predicate
     * @param Product $product
     * @param Product|null $parent
     * @return bool
     */
    public function matches(array $predicate, Product $product, ?Product $parent = null): bool
    {
        $key = json_encode($predicate, JSON_THROW_ON_ERROR);
        if (!isset($this->compiled[$key])) {
            $predicate = $this->configuration->validateConditions($predicate);
            if (count($this->compiled) >= 100) {
                $this->compiled = [];
            }
            $conditions = [];
            foreach ($predicate['conditions'] as $row) {
                $condition = $this->conditionFactory->create();
                $condition->setAttribute($row['attribute']);
                $condition->setOperator($row['operator']);
                $condition->setValue($row['value']);
                $conditions[] = ['scope' => $row['scope'] ?? 'product', 'condition' => $condition];
            }
            $this->compiled[$key] = ['all' => $predicate['aggregator'] === 'all', 'conditions' => $conditions];
        }
        $compiled = $this->compiled[$key];
        foreach ($compiled['conditions'] as $row) {
            $subject = $row['scope'] === 'parent' ? $parent : $product;
            $valid = $subject !== null && (clone $row['condition'])->validate($subject);
            if ($valid !== $compiled['all']) {
                return !$compiled['all'];
            }
        }
        return $compiled['all'];
    }
}
