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

namespace MageOS\ShoppingFeed\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use MageOS\ShoppingFeed\Model\FeedFactory;

class File extends Column
{
    /**
     * Feed model factory
     *
     * @var \MageOS\ShoppingFeed\Model\FeedFactory
     */
    protected $feedFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param FeedFactory $feedFactory
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        FeedFactory $feedFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        private \Magento\Framework\Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->feedFactory = $feedFactory;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param  array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $mageRootPath = $this->directoryList->getRoot();

            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['id'])) {
                    $feed = $this->feedFactory->create()->setData($item);
                    $fileInformation = $feed->getMessages();
                    $filepath = $fileInformation['file'] ?? '';
                    $item[$name] = $this->escaper->escapeHtml((string) __('Feed file not ready.'));
                    if (!is_string($filepath) || $filepath === '' || !isset($fileInformation['skipped'])) {
                        continue;
                    }
                    $absolute = rtrim($mageRootPath, '/') . '/' . ltrim($filepath, '/');
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction -- realpath() media containment check; a filesystem driver needs a constructor change
                    $path = realpath($absolute);
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction -- realpath() media containment check; a filesystem driver needs a constructor change
                    $temporary = realpath($absolute . '.tmp');
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction -- realpath() media containment check; a filesystem driver needs a constructor change
                    $media = realpath($this->directoryList->getPath('media'));
                    $existing = $path ?: $temporary;
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction -- is_file() on the contained path; a filesystem driver needs a constructor change
                    if (!$media || !$existing || !is_file($existing) || !str_starts_with($existing, $media . '/')) {
                        continue;
                    }
                    if ($path) {
                        $store = $this->storeManager->getStore(
                            (int) ($fileInformation['store_id'] ?? $feed->getStoreId())
                        );
                        $url = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                            . substr($path, strlen($media) + 1);
                        // The file name keeps the grid narrow; the full address stays in the link and its tooltip.
                        $item[$name] = '<a class="shopping-feed-file" href="' . $this->escaper->escapeUrl($url)
                            . '" title="' . $this->escaper->escapeHtml($url)
                            . '" target="_blank" rel="noopener">'
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction -- basename() of a validated media path
                            . $this->escaper->escapeHtml(basename($path)) . '</a>';
                    }
                    $summary = (string) __(
                        '%4 - processed %1 products, added %2 rows, %3 rows skipped',
                        (int) ($fileInformation['added'] ?? 0),
                        (int) ($fileInformation['exported'] ?? 0),
                        (int) $fileInformation['skipped'],
                        is_string($fileInformation['date'] ?? null) ? $fileInformation['date'] : ''
                    );
                    $item[$name] .= '<br /><span class="shopping-feed-file-summary">'
                        . $this->escaper->escapeHtml($summary) . '</span>';
                }
            }
        }

        return $dataSource;
    }
}
