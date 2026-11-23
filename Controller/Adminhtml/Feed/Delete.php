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

class Delete extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_ShoppingFeed::delete';

    /**
     * @var \MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Builder
     */
    protected $feedBuilder;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Builder $feedBuilder
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Builder $feedBuilder
    ) {
        $this->feedBuilder = $feedBuilder;

        parent::__construct($context);
    }

    /**
     * Delete individual feed
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $feed = $this->feedBuilder->build($this->getRequest()->getParams());

        if (!$feed->getId()) {
            $this->messageManager->addErrorMessage(__('Feed wasn\'t found'));
        } else {
            $feed->delete();
            $this->messageManager->addSuccessMessage(__('Feed has been deleted'));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('mageos_shopping_feed/feed/index');
    }
}
