<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Product\ModifierChain;

/** Validate modifier rows on every feed configuration save, including imports. */
class ValidateColumnModifiers implements ObserverInterface
{
    /**
     * Constructor.
     *
     * @param ModifierChain $modifiers
     */
    public function __construct(private ModifierChain $modifiers)
    {
    }

    /**
     * Reject malformed chains and persist an explicit clear request.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $feed = $observer->getEvent()->getData('object');
        if (!$feed instanceof Feed || $feed->getData('no_after_save')) {
            return;
        }
        $value = $feed->getData('config', ModifierChain::CONFIG_KEY);
        $chains = $this->modifiers->compileForFeed($feed);
        // Read the submitted map directly; getColumnsMap() may still cache the previous map.
        $columns = [];
        $mappings = $chains
            ? $feed->normalizeColumnsMap((array)$feed->getData('config', 'columns_product_columns')) : [];
        foreach ($mappings as $mapping) {
            if (is_array($mapping) && is_scalar($mapping['column'] ?? null)) {
                $columns[] = (string)$mapping['column'];
            }
        }
        foreach (array_keys($chains) as $column) {
            if (!in_array((string)$column, $columns, true)) {
                throw new LocalizedException(__(
                    'Column modifier refers to unknown output column "%1". Use an exact name from Columns Map.',
                    $column
                ));
            }
        }
        if ($value === null && $feed->getConfig()->hasData(ModifierChain::CONFIG_KEY)) {
            $feed->getConfig()->setData(ModifierChain::CONFIG_KEY, []);
        }
    }
}
