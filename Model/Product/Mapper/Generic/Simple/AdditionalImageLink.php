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

namespace MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple;

use \MageOS\ShoppingFeed\Model\Product\Mapper\MapperAbstract;

class AdditionalImageLink extends MapperAbstract
{
    /**
     * @var \Magento\Catalog\Model\Product\Gallery\ReadHandler
     */
    protected $galleryHandler;

    /**
     * Additional image links mapper constructor.
     *
     * @param \MageOS\ShoppingFeed\Model\Logger $logger
     * @param \Magento\Catalog\Model\Product\Gallery\ReadHandler $galleryHandler
     */
    public function __construct(
        \MageOS\ShoppingFeed\Model\Logger $logger,
        \Magento\Catalog\Model\Product\Gallery\ReadHandler $galleryHandler
    ) {

        $this->galleryHandler = $galleryHandler;
        parent::__construct($logger);
    }

    /**
     * Map additional images without flattening XML list boundaries.
     *
     * @param array $params
     * @return string|\MageOS\ShoppingFeed\Model\Output\MappedValue
     */
    public function map(array $params = [])
    {
        /**
         * @var \Magento\Catalog\Model\Product $product
        */
        $product = $this->getAdapter()->getProduct();
        $imageType = !empty($params['param']) ? $params['param'] : 'image';

        if (($baseImage = $product->getData($imageType)) != "") {
            $baseImage = $this->getAdapter()->getData('images_url_prefix') . '/' . ltrim($baseImage, '/');
        }

        $this->galleryHandler->execute($product);
        $mediaGalleryImages = $product->getMediaGalleryImages();

        $urls = [];
        $c = 0;
        if (is_array($mediaGalleryImages) || $mediaGalleryImages instanceof \Magento\Framework\Data\Collection) {
            foreach ($mediaGalleryImages as $image) {
                if (++$c > 10) {
                    break;
                }
                if ($image['disabled']) {
                    continue;
                }
                $img = $this->getAdapter()->getData('images_url_prefix') . '/' . ltrim($image['file'], '/');

                if ($baseImage && strcmp($baseImage, $img) == 0) {
                    continue;
                }

                $urls[] = $img;
            }
        }
        $glue = $this->hasConfiguration('separator') ? (string)$this->getConfiguration('separator') : ",";
        $feed = $this->getAdapter()->getFeed();
        $delimiter = $feed->getConfig('output_params_delimiter', "\t");
        $delimiter_other = $feed->getConfig('output_params_delimiter_other', "\t");
        if ($feed->getConfig('output_params_format', 'delimited') !== 'xml'
            && !$this->hasConfiguration('separator') && (trim($delimiter) == $glue || trim($delimiter_other) == $glue)
        ) {
            $glue = "|";
        }
        if ($feed->getConfig('output_params_format', 'delimited') === 'xml' && $urls !== []) {
            foreach ($urls as &$url) {
                $this->getAdapter()->getFilter()->findAndReplace($url, $params['column'] ?? '');
            }
            unset($url);
            return new \MageOS\ShoppingFeed\Model\Output\MappedValue($urls, implode($glue, $urls), $glue);
        }
        $cell = implode($glue, $urls);
        $this->getAdapter()->getFilter()->findAndReplace($cell, $params['column'] ?? '');
        return $cell;
    }
}
