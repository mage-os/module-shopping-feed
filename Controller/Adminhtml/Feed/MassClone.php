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
use MageOS\ShoppingFeed\Model\ResourceModel\Feed\CollectionFactory;

class MassClone extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_ShoppingFeed::save';

    /**
     * Massactions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\Copier
     */
    protected $copier;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param \MageOS\ShoppingFeed\Model\Feed\Copier $copier
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \MageOS\ShoppingFeed\Model\Feed\Copier $copier
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->copier = $copier;
        parent::__construct($context);
    }

    /**
     * Enable selected feeds
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        /** @var \MageOS\ShoppingFeed\Model\Feed $feed */
        foreach ($collection->getItems() as $feed) {
            $this->copier->copy($feed);
        }
        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been cloned.', $collection->getSize())
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('mageos_shopping_feed/feed/index');
    }
}
