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

class Save extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_ShoppingFeed::save';

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\Converter
     */
    protected $feedConverter;

    /**
     * @param \MageOS\ShoppingFeed\Model\Feed\Converter $feedConverter
     * @param \Magento\Backend\App\Action\Context $context
     * @param \MageOS\ShoppingFeed\Model\Adminhtml\FeedFormData $formData
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \MageOS\ShoppingFeed\Model\Feed\Converter $feedConverter,
        \Magento\Backend\App\Action\Context $context,
        private \MageOS\ShoppingFeed\Model\Adminhtml\FeedFormData $formData,
        private \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
    ) {
        $this->feedConverter = $feedConverter;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageOS_ShoppingFeed::save');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $formData = $this->getRequest()->getParams();
        /**
 * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
*/
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($formData) {
            try {
                if (array_key_exists('feed_form_data', $formData)) {
                    $formData = $this->formData->decode($formData['feed_form_data']);
                    // Retain the existing prepare-save observer request contract.
                    $this->getRequest()->setParams($formData);
                }
                $feed = $this->feedConverter->populateFeedData($formData);
                $this->_eventManager->dispatch(
                    'mageos_shopping_feed_feed_prepare_save',
                    ['feed' => $feed, 'request' => $this->getRequest()]
                );
                $feed->save();
                $this->messageManager->addSuccessMessage(__('You saved this feed.'));
                $this->_getSession()->setMageosShoppingFeedData(false);
                $this->dataPersistor->clear(\MageOS\ShoppingFeed\Model\Adminhtml\FeedFormData::PERSISTOR_KEY);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $feed->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the feed.'));
            }

            // Do not persist the raw envelope, decrypted model data, or newly typed passwords.
            unset($formData['feed_form_data'], $formData['form_key']);
            if (isset($feed)) {
                $formData['id'] = $feed->getId();
                $formData['type'] = $feed->getType();
                $formData['store_id'] = $feed->getStoreId();
            }
            $this->dataPersistor->set(
                \MageOS\ShoppingFeed\Model\Adminhtml\FeedFormData::PERSISTOR_KEY,
                $this->formData->redact($formData)
            );
            if (!empty($formData['uploads'])) {
                $this->messageManager->addNoticeMessage(
                    __('Re-enter any new or changed upload passwords before saving again.')
                );
            }
            $redirect = [];
            foreach (['id', 'type', 'store_id'] as $key) {
                if (isset($formData[$key]) && is_scalar($formData[$key])) {
                    $redirect[$key] = $formData[$key];
                }
            }
            return $resultRedirect->setPath(
                !empty($redirect['id']) || !empty($redirect['type']) ? '*/*/edit' : '*/*/',
                $redirect
            );
        }
        return $resultRedirect->setPath('*/*/');
    }
}
