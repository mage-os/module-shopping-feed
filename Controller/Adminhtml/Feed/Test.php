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

use Magento\Framework\Exception\LocalizedException;

class Test extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'MageOS_ShoppingFeed::generate';

    /**
     * @var \MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Builder
     */
    protected $feedBuilder;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Test constructor.
     *
     * @param \Magento\Backend\App\Action\Context               $context
     * @param Builder                                           $feedBuilder
     * @param \Magento\Framework\View\Result\PageFactory        $resultPageFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Catalog\Model\ProductFactory             $productFactory
     * @param \Magento\Framework\Registry                       $registry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Builder $feedBuilder,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->feedBuilder = $feedBuilder;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->productFactory = $productFactory;
        $this->registry = $registry;

        parent::__construct($context);
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

    /**
     * Create new feed page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            // Preview's type is the lookup mode, not a feed preset.
            $feed = $this->feedBuilder->build(['id' => $this->getRequest()->getParam('id')]);
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $this->resultForwardFactory->create()->forward('index');
        }

        if (!$feed->getId()) {
            $this->messageManager->addErrorMessage(__('Feed wasn\'t found'));
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('index');
        }

        try {
            [$sku, $type] = $this->lookup();
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $sku = '';
        }

        if ($sku !== '') {
            $product = $this->productFactory->create();

            switch ($type) {
                case 'id':
                    $product->load($sku);
                    break;
                case 'sku':
                default:
                    $product->load($product->getIdBySku($sku));
                    break;
            }

            if (!$product->getId()) {
                $this->messageManager->addErrorMessage(
                    $type === 'id'
                        ? __('Could not find any product with this ID.')
                        : __('Could not find any product with this SKU.')
                );
            } else {
                $this->registry->register('current_test_product', $product);
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Test Feed #%1 [%2]', $feed->getId(), $feed->getName()));

        return $resultPage;
    }

    private function lookup(): array
    {
        $type = $this->getRequest()->getParam('type', 'sku');
        if (!in_array($type, ['sku', 'id'], true)) {
            throw new LocalizedException(__('Choose SKU or Product ID to test a product.'));
        }
        $sku = $this->getRequest()->getParam('sku', '');
        if (!is_string($sku) && !is_int($sku)) {
            throw new LocalizedException(__('Enter a valid SKU or Product ID.'));
        }
        $sku = (string)$sku;
        if ($type === 'id' && $sku !== '') {
            $id = ctype_digit($sku) ? filter_var(
                ltrim($sku, '0') ?: '0',
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            ) : false;
            if ($id === false) {
                throw new LocalizedException(__('Product ID must be a positive integer.'));
            }
            return [$id, $type];
        }
        return [$sku, $type];
    }
}
