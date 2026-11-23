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

namespace MageOS\ShoppingFeed\Ui\Component\Listing\Column\File;

use Magento\Store\Model\StoreManagerInterface;
use MageOS\ShoppingFeed\Model\FeedFactory;

class Plugin
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FeedFactory
     */
    protected $feedFactory;

    /**
     * Constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param FeedFactory $feedFactory
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        FeedFactory $feedFactory,
        private \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        private \Magento\Framework\Escaper $escaper
    ) {

        $this->feedFactory = $feedFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Append the promotions feed file link and row count to the File column of the feed grid.
     *
     * @param \MageOS\ShoppingFeed\Ui\Component\Listing\Column\File $subject
     * @param mixed[] $dataSource
     * @return mixed[]
     */
    public function afterPrepareDataSource($subject, $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $subject->getData('name');

                if (isset($item['id'])) {
                    $feed = $this->feedFactory->create()->setData($item);
                    $messages = $feed->getMessages();
                    $filepath = $messages['promotion_file'] ?? '';
                    if (!is_string($filepath) || $filepath === '' || !isset($messages['promotion_added'])) {
                        continue;
                    }
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction -- realpath() media containment check; a filesystem driver needs a constructor change
                    $path = realpath($filepath);
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction -- realpath() media containment check; a filesystem driver needs a constructor change
                    $media = realpath($this->directoryList->getPath('media'));
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction -- is_file() on the contained path; a filesystem driver needs a constructor change
                    if ($media && $path && is_file($path) && str_starts_with($path, $media . '/')) {
                        $store = $this->storeManager->getStore((int)$feed->getStoreId());
                        $url = sprintf(
                            '%s%s',
                            $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA),
                            substr($path, strlen($media) + 1)
                        );
                        $item[$name] .= '<br /><a class="shopping-feed-file" href="' . $this->escaper->escapeUrl($url)
                            . '" title="' . $this->escaper->escapeHtml($url) . '" target="_blank" rel="noopener">'
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction -- basename() of a validated media path
                            . $this->escaper->escapeHtml(basename($path)) . '</a><br />'
                            . '<span class="shopping-feed-file-summary">' . $this->escaper->escapeHtml(
                                (string) __('%1 promotion rows', (int) $messages['promotion_added'])
                            ) . '</span>';
                    }
                }
            }
        }

        return $dataSource;
    }
}
