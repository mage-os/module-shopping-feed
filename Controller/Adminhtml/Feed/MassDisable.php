<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_ShoppingFeed
 * @copyright Copyright (c) 2016 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */

namespace MageOS\ShoppingFeed\Controller\Adminhtml\Feed;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use MageOS\ShoppingFeed\Model\ResourceModel\Feed\CollectionFactory as FeedCollectionFactory;
use MageOS\ShoppingFeed\Model\Feed\Source\Status;
use MageOS\ShoppingFeed\Model\ResourceModel\Generator\Queue\CollectionFactory as QueueCollectionFactory;

class MassDisable extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_ShoppingFeed::save';

    /**
     * Massactions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * Writes the status column only
     *
     * @var \MageOS\ShoppingFeed\Model\ResourceModel\Feed
     */
    protected $feedResource;

    /**
     * @var FeedCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var QueueCollectionFactory
     */
    protected $queueCollectionFactory;

    /**
     * @param Context                                       $context
     * @param Filter                                        $filter
     * @param FeedCollectionFactory                         $collectionFactory
     * @param QueueCollectionFactory                        $queueCollectionFactory
     * @param \MageOS\ShoppingFeed\Model\ResourceModel\Feed $feedResource
     */
    public function __construct(
        Context $context,
        Filter $filter,
        FeedCollectionFactory $collectionFactory,
        QueueCollectionFactory $queueCollectionFactory,
        \MageOS\ShoppingFeed\Model\ResourceModel\Feed $feedResource
    ) {
        $this->feedResource = $feedResource;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->queueCollectionFactory = $queueCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Disable selected feeds
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $feedIds = [];

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        /** @var \MageOS\ShoppingFeed\Model\Feed $feed */
        foreach ($collection->getItems() as $feed) {
            // Writes only the status column: stored rules are not revalidated and generator progress is kept.
            $previous = $feed->getData('status');
            $this->feedResource->updateStatus((int)$feed->getId(), Status::STATUS_DISABLED);
            $feed->setData('status', Status::STATUS_DISABLED);
            $this->_eventManager->dispatch('mageos_shopping_feed_feed_status_change', [
                'feed' => $feed,
                'status' => Status::STATUS_DISABLED,
                'previous_status' => $previous,
            ]);
            $feedIds[] = $feed->getId();
        }

        $queueItems = $this->queueCollectionFactory->create()
            ->addFieldToFilter('feed_id', ['in'=> $feedIds])
            ->getItems();
        /** @var \MageOS\ShoppingFeed\Model\Generator\Queue $item */
        foreach ($queueItems as $item) {
            $item->delete();
        }

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been disabled.', $collection->getSize())
        );
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('mageos_shopping_feed/feed/index');
    }
}
