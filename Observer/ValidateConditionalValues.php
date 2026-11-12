<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Observer;

use MageOS\ShoppingFeed\Model\ConditionalValue\Configuration;
use MageOS\ShoppingFeed\Model\Feed;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/** Enforce the same parameter contract for Admin, import, and programmatic model saves. */
class ValidateConditionalValues implements ObserverInterface
{
    /**
     * Constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Validate column and empty-value fallback mappings before persistence.
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
        foreach (['columns_product_columns', 'filters_map_replace_empty_columns'] as $key) {
            foreach ((array)$feed->getConfig($key, []) as $row) {
                if (is_array($row) && ($row['attribute'] ?? '') === Configuration::DIRECTIVE) {
                    $this->configuration->validate($row['param'] ?? null);
                }
            }
        }
    }
}
