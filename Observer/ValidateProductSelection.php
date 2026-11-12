<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Observer;

use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\ProductSelection\Selection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/** Validate Admin, imported and programmatic feed saves. */
class ValidateProductSelection implements ObserverInterface
{
    /**
     * Constructor.
     *
     * @param Selection $selection
     */
    public function __construct(private Selection $selection)
    {
    }

    /**
     * Reject invalid predicates before any feed configuration is persisted.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $feed = $observer->getEvent()->getData('object');
        if ($feed instanceof Feed && !$feed->getData('no_after_save')) {
            $value = $feed->getData('config', Selection::CONFIG_KEY);
            $this->selection->validate($value);
            if ($value === null && $feed->getConfig()->hasData(Selection::CONFIG_KEY)) {
                $feed->getConfig()->setData(Selection::CONFIG_KEY, []);
            }
        }
    }
}
