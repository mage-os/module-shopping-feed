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

namespace MageOS\ShoppingFeed\Model\Product\Adapter\Type;

use \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterInterface;
use Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory as LinkedProductCollectionFactory;
use Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\CollectionFactory as RulePriceCollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection as ChildCollection;

/**
 * Configurable Adapter, holds business logic between Product, Config and Mapper
 *
 * Class Configurable
 *
 */
class Configurable extends Composite implements AdapterInterface
{
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * Configurable adapter constructor.
     *
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \MageOS\ShoppingFeed\Model\Feed $feed
     * @param \Magento\Catalog\Model\Product $product
     * @param \MageOS\ShoppingFeed\Model\FeedTypes\Config $feedTypesConfig
     * @param \MageOS\ShoppingFeed\Model\Product\Mapper\MapperFactory $mapperFactory
     * @param \MageOS\ShoppingFeed\Model\Product\Helper $helper
     * @param \Magento\Weee\Helper\Data $weeeData
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \MageOS\ShoppingFeed\Model\Product\Helper\Catalog $catalogHelper
     * @param RulePriceCollectionFactory $catalogRuleCollectionFactory
     * @param \Magento\Catalog\Model\Product\Type\Price $productTypePrice
     * @param \Magento\CatalogInventory\Model\StockState $stockState
     * @param \MageOS\ShoppingFeed\Model\Product\Filter $filter
     * @param \Magento\Framework\Locale\Resolver $localeResolver
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Framework\Stdlib\DateTime $date
     * @param \MageOS\ShoppingFeed\Model\Product\Processors\OptionFactory $optionFactory
     * @param \MageOS\ShoppingFeed\Model\Generator\ProcessFactory $processFactory
     * @param \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\CollectionFactory $processCollectionFactory
     * @param \MageOS\ShoppingFeed\Model\Logger $logger
     * @param \MageOS\ShoppingFeed\Model\Generator\Cache $cache
     * @param \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterFactory $adapterFactory
     * @param \MageOS\ShoppingFeed\Model\Product\Formatter\FormatterFactory $formatterFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \MageOS\ShoppingFeed\Model\Inventory\Api $sourceInventoryApi
     * @param \Magento\Framework\Serialize\SerializerInterface $jsonSerializer
     * @param \MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Concatenate $concatenateMapper
     * @param LinkedProductCollectionFactory $linkedProductCollectionFactory
     * @param \MageOS\ShoppingFeed\Model\ProductSelection\Selection $productSelection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \MageOS\ShoppingFeed\Model\Feed $feed,
        \Magento\Catalog\Model\Product $product,
        \MageOS\ShoppingFeed\Model\FeedTypes\Config $feedTypesConfig,
        \MageOS\ShoppingFeed\Model\Product\Mapper\MapperFactory $mapperFactory,
        \MageOS\ShoppingFeed\Model\Product\Helper $helper,
        \Magento\Weee\Helper\Data $weeeData,
        \Magento\Tax\Helper\Data $taxData,
        \MageOS\ShoppingFeed\Model\Product\Helper\Catalog $catalogHelper,
        \Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\CollectionFactory $catalogRuleCollectionFactory,
        \Magento\Catalog\Model\Product\Type\Price $productTypePrice,
        \Magento\CatalogInventory\Model\StockState $stockState,
        \MageOS\ShoppingFeed\Model\Product\Filter $filter,
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Framework\Stdlib\DateTime $date,
        \MageOS\ShoppingFeed\Model\Product\Processors\OptionFactory $optionFactory,
        \MageOS\ShoppingFeed\Model\Generator\ProcessFactory $processFactory,
        \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\CollectionFactory $processCollectionFactory,
        \MageOS\ShoppingFeed\Model\Logger $logger,
        \MageOS\ShoppingFeed\Model\Generator\Cache $cache,
        \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterFactory $adapterFactory,
        \MageOS\ShoppingFeed\Model\Product\Formatter\FormatterFactory $formatterFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \MageOS\ShoppingFeed\Model\Inventory\Api $sourceInventoryApi,
        \Magento\Framework\Serialize\SerializerInterface $jsonSerializer,
        \MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Concatenate $concatenateMapper,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $linkedProductCollectionFactory,
        \MageOS\ShoppingFeed\Model\ProductSelection\Selection $productSelection,
        array $data = []
    ) {

        $this->productMetadata = $productMetadata;
        parent::__construct(
            $filesystem,
            $feed,
            $product,
            $feedTypesConfig,
            $mapperFactory,
            $helper,
            $weeeData,
            $taxData,
            $catalogHelper,
            $catalogRuleCollectionFactory,
            $productTypePrice,
            $stockState,
            $filter,
            $localeResolver,
            $timezone,
            $date,
            $optionFactory,
            $processFactory,
            $processCollectionFactory,
            $logger,
            $cache,
            $adapterFactory,
            $formatterFactory,
            $categoryCollectionFactory,
            $sourceInventoryApi,
            $jsonSerializer,
            $concatenateMapper,
            $linkedProductCollectionFactory,
            $productSelection,
            $data
        );
    }

    /**
     * Includes native attribute IDs for Hyva and codes for legacy swatch renderers.
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getUrlOptions(\Magento\Catalog\Model\Product $product)
    {
        $params = [];
        if ($this->getFeed()->getConfig('configurable_associated_products_link_add_unique')) {
            /**
 * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProductType
*/
            $configurableProductType = $this->getProduct()->getTypeInstance();
            $codes = $configurableProductType->getConfigurableAttributes($this->getProduct());

            /**
 * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute $attribute
*/
            foreach ($codes as $attribute) {
                $eavAttribute = $attribute->getProductAttribute();
                $code = $eavAttribute->getAttributeCode();
                if (!$product->hasData($code)) {
                    continue;
                }
                $id = $attribute->getAttributeId();
                $value = $product->getData($code);

                $params[$id] = $value;
                if ($this->useAttributeCodeForUrl($attribute)) {
                    $params[$code] = $value;
                }
            }
        }

        return $params;
    }

    /**
     * @inheritdoc
     */
    public function beforeMap()
    {
        if (!$this->hasData('associated_product_adapters')
            || !is_array($this->getData('associated_product_adapters'))
        ) {
            // Get associated products with this one
            $configurableProduct = $this->getProduct();
            /**
             * AdapterFactory picks this adapter by the product's type ID, so the type instance is configurable.
             *
             * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableTypeInstance
             */
            $configurableTypeInstance = $configurableProduct->getTypeInstance();
            /**
 * @var ChildCollection $associatedProductCollection
*/
            $associatedProductCollection = $configurableTypeInstance
                ->setStoreFilter($this->getStore(), $configurableProduct)
                ->getUsedProductCollection($configurableProduct)
                ->setFlag('has_stock_status_filter', true)
                ->addAttributeToSelect('*');

            $associatedProductAdapters = $this->prepareAssociatedProductAdapters($associatedProductCollection);

            $this->setData('associated_product_adapters', $associatedProductAdapters);
        }

        return parent::beforeMap();
    }

    /**
     * @inheritdoc
     */
    public function getAssociatedProductsMode()
    {
        return $this->getFeed()->getConfig('configurable_associated_products_mode');
    }

    /**
     * @inheritdoc
     */
    public function getAssociatedMapInheritance()
    {
        return $this->getFeed()->getConfig('configurable_map_inherit', []);
    }

    /**
     * Whether the URL also carries the attribute code, for swatch attributes on Magento 2.0.13 and later.
     *
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute $attribute
     * @return bool
     */
    public function useAttributeCodeForUrl($attribute)
    {
        /**
 * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute $attribute
*/
        $swatch = $attribute->getProductAttribute()->hasData('swatch_input_type');
        $mageVersion = $this->productMetadata->getVersion();

        return ($swatch && version_compare($mageVersion, '2.0.13', '>='));
    }
}
