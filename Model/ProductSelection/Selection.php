<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\ProductSelection;

use MageOS\ShoppingFeed\Model\ConditionalValue\Configuration;
use MageOS\ShoppingFeed\Model\ConditionalValue\Matcher;
use MageOS\ShoppingFeed\Model\Feed;
use Magento\Catalog\Model\Product;

/** Additional eligibility predicate; never broadens the existing feed filters. */
class Selection
{
    public const CONFIG_KEY = 'filters_product_conditions';

    /** @var array<string, array<string, mixed>|null> */
    private array $validated = [];

    /**
     * Constructor.
     *
     * @param Configuration $configuration
     * @param Matcher $matcher
     * @param Parents $parents
     */
    public function __construct(
        private Configuration $configuration,
        private Matcher $matcher,
        private Parents $parents
    ) {
    }

    /**
     * Reset store-scoped parent data for every generation, including each resumed batch.
     *
     * @param mixed $value
     * @return void
     */
    public function beginRun(mixed $value): void
    {
        // Attribute existence/options may have changed since a preceding feed ran.
        $this->validated = [];
        $expression = $this->validate($value);
        $attributes = [];
        foreach ($expression['conditions'] ?? [] as $row) {
            if (($row['scope'] ?? 'product') === 'parent' && $row['attribute'] !== 'category_ids') {
                $attributes[] = $row['attribute'] === 'has_image' ? 'image' : $row['attribute'];
            }
        }
        $this->parents->reset(array_values(array_unique($attributes)));
    }

    /**
     * Empty rules disable selection; malformed configured rules fail save and generation.
     *
     * @param mixed $value
     * @return array<string, mixed>|null
     */
    public function validate(mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }
        if (is_array($value) && ($value['conditions'] ?? null) === []
            && in_array($value['aggregator'] ?? null, ['all', 'any'], true)
        ) {
            return null;
        }
        $key = json_encode($value, JSON_THROW_ON_ERROR);
        if (!array_key_exists($key, $this->validated)) {
            if (count($this->validated) >= 100) {
                $this->validated = [];
            }
            $this->validated[$key] = $this->configuration->validateConditions($value);
        }
        return $this->validated[$key];
    }

    /**
     * Require one complete match against the active parent, or any one direct parent for standalone rows.
     *
     * @param Feed $feed
     * @param Product $product
     * @param Product|null $parent
     * @return bool
     */
    public function isExcluded(Feed $feed, Product $product, ?Product $parent = null): bool
    {
        $expression = $this->validate($feed->getData('config', self::CONFIG_KEY));
        if ($expression === null) {
            return false;
        }
        $usesParent = in_array('parent', array_column($expression['conditions'], 'scope'), true);
        if (!$usesParent || $parent !== null) {
            return !$this->matcher->matches($expression, $product, $parent);
        }
        $found = false;
        foreach ($this->parents->get($product, (int)$feed->getStoreId()) as $candidate) {
            $found = true;
            if ($this->matcher->matches($expression, $product, $candidate)) {
                return false;
            }
        }
        return $found || !$this->matcher->matches($expression, $product);
    }
}
