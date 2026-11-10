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

use \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract;
use \MageOS\ShoppingFeed\Model\Feed\Source\Product\Inheritance;
use Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory as LinkedProductCollectionFactory;
use Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\CollectionFactory as RulePriceCollectionFactory;

/**
 * Composite Adapter, holds business logic between Product, Config and Mapper
 *
 * Class Composite
 *
 */
class Composite extends AdapterAbstract
{
    /**
     * Associated-products modes that output the parent product row
     *
     * @var int[]
     */
    protected $allowed_parent = [
        \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::ONLY_PARENT,
        \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::BOTH_PARENT_ASSOCIATED
    ];

    /**
     * Associated-products modes that output the associated product rows
     *
     * @var int[]
     */
    protected $allowed_assoc = [
        \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::ONLY_ASSOCIATED,
        \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::BOTH_PARENT_ASSOCIATED
    ];

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Concatenate
     */
    protected $concatenateMapper;

    /**
     * @var array
     */
    protected $skippedData = [];

    /** @var \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory */
    protected $linkedProductCollectionFactory;

    /**
     * Composite adapter constructor.
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
        \MageOS\ShoppingFeed\Model\Inventory\Api $sourceInventoryApi,
        \Magento\Framework\Serialize\SerializerInterface $jsonSerializer,
        \MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Concatenate $concatenateMapper,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $linkedProductCollectionFactory,
        \MageOS\ShoppingFeed\Model\ProductSelection\Selection $productSelection,
        array $data = []
    ) {
        $this->concatenateMapper = $concatenateMapper;
        $this->linkedProductCollectionFactory = $linkedProductCollectionFactory;

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
            $productSelection,
            $data
        );
    }

    /**
     * Build an adapter for each enabled associated product, linked to this parent adapter.
     *
     * @param  \Magento\Catalog\Model\ResourceModel\Product\Collection $associatedProductCollection
     * @return array
     */
    protected function prepareAssociatedProductAdapters($associatedProductCollection)
    {
        $associatedProductAdapters = [];

        foreach ($associatedProductCollection as $associatedProduct) {
            /**
 * @var \Magento\Catalog\Model\Product $associatedProduct
*/
            if ($associatedProduct->isDisabled()) {
                continue;
            }

            $associatedProductAdapter = $this->adapterFactory->create($associatedProduct, $this->getFeed(), false);
            if ($associatedProductAdapter !== false) {
                $associatedProductAdapter->setParentAdapter($this)
                    ->setData('generator', $this->getGenerator())
                    ->setData('associated_product_adapters', []);
                if ($this->isTestMode()) {
                    $associatedProductAdapter->setTestMode();
                }
                $associatedProductAdapters[] = $associatedProductAdapter;
            }
        }

        return $associatedProductAdapters;
    }

    /**
     * Internal method to pull feed config for specific product type
     *
     * This needs to be overwritten in child class
     *
     * @return int
     */
    public function getAssociatedProductsMode()
    {
        return \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::BOTH_PARENT_ASSOCIATED;
    }

    /**
     * Internal method to pull feed config for specific product type
     *
     * This needs to be overwritten in child class
     *
     * @return array
     */
    public function getAssociatedMapInheritance()
    {
        return [];
    }

    /**
     * @inheritdoc
     * @param bool $checkDuplicates
     * @param bool $mapAssociatedProducts
     */
    public function internalMap(bool $checkDuplicates = true, bool $mapAssociatedProducts = true): array
    {
        $associatedMode = $this->getAssociatedProductsMode();
        $rows = [];

        if (in_array($associatedMode, $this->allowed_parent) && !$this->isExcludedByConditions()) {
            $this->setData('map_parent', true);
            // Map current product
            $fields = [];
            foreach ($this->feed->getColumnsMap() as $arr) {
                $column = $arr['column'];
                $row = $this->getMapValue($arr);

                if (isset($fields[$column])) {
                    if (is_array($fields[$column])) {
                        $fields[$column][] = $row;
                    } else {
                        $fields[$column] = [$fields[$column], $row];
                    }
                } else {
                    $fields[$column] = $row;
                }
            }
            $rows[] = $fields;
        }

        if ($mapAssociatedProducts && in_array($associatedMode, $this->allowed_assoc)) {
            $rows = array_merge($rows, $this->mapAssociatedProducts($checkDuplicates));
        }

        return $rows;
    }

    /**
     * Collect the skip reason locally; afterMap() logs the skipped associated products per parent.
     *
     * @param string $reason
     * @param int|string|null $productId
     * @return $this
     */
    public function setSkipProduct($reason, $productId = null)
    {
        if ($productId === null) {
            $productId = $this->getProduct()->getId();
        }

        // Set skipped data locally instead of Generator
        if (isset($this->skippedData[$reason])) {
            array_push($this->skippedData[$reason], $productId);
        } else {
            $this->skippedData[$reason] = [$productId];
        }

        $this->setData('is_skipped', true);

        return $this;
    }

    /**
     * Get rows for associated products
     *
     * @param bool $checkDuplicates
     * @return array
     */
    public function mapAssociatedProducts(bool $checkDuplicates = true): array
    {
        $rows = [];
        $associatedProductAdapters = $this->getData('associated_product_adapters');
        $feedType = $this->feed->getType();

        /**
 * @var \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract $associatedProductAdapter
*/
        foreach ($associatedProductAdapters as $associatedProductAdapter) {
            if ($associatedProductAdapter->isSkipped()
                || $associatedProductAdapter->isExcludedByConditions()
                || ($checkDuplicates && $associatedProductAdapter->isDuplicate())
            ) {
                continue;
            }

            $fields = [];
            foreach ($this->feed->getColumnsMap() as $arr) {
                $cell = $this->mapByInheritance($associatedProductAdapter, $arr);
                // Grab from associated by default if no inheritance rule defined
                if ($cell === false) {
                    $cell = $associatedProductAdapter->getMapValue($arr);
                }

                $column = $arr['column'];
                $directive = $this->feedTypesConfig->getDirective($feedType, $arr['attribute']);
                if (!empty($directive)) {
                    $mapperData = $this->mapperFactory->getMapperData($directive, $feedType);
                    $mapper = $this->mapperFactory->create($directive, $associatedProductAdapter);
                    if (isset($mapperData['filter']) && $mapperData['filter']) {
                        $skip = $mapper->filter($cell);
                        if ($skip) {
                            $this->setSkipProduct(sprintf('filtered by column "%s"', $column));
                            continue 2;
                        }
                    }
                }

                if (isset($fields[$column])) {
                    if (is_array($fields[$column])) {
                        $fields[$column][] = $cell;
                    } else {
                        $fields[$column] = [$fields[$column], $cell];
                    }
                } else {
                    $fields[$column] = $cell;
                }
            }

            array_push($rows, $fields);
        }

        return $rows;
    }

    /**
     * @inheritdoc
     */
    protected function afterMap(array $rows)
    {
        $rows = parent::afterMap($rows);

        // Log skipped
        foreach ($this->skippedData as $message => $ids) {
            $this->logger->info(
                sprintf(
                    "Skipped associated product id(s) [%s], parent %s - %s",
                    implode(', ', $ids),
                    $this->getProduct()->getId(),
                    $message
                )
            );
        }
        $this->skippedData = [];

        return $rows;
    }

    /**
     * @inheritdoc
     */
    public function getChildrenCount()
    {
        $associatedAdapters = $this->getData('associated_product_adapters');
        return is_array($associatedAdapters)
            ? self::DEFAULT_CHILDREN_COUNT + count($associatedAdapters)
            : self::DEFAULT_CHILDREN_COUNT;
    }

    /**
     * @inheritdoc
     */
    public function hasSpecialPrice($processRules = true, $product = null)
    {
        $associatedProductAdapters = $this->getData('associated_product_adapters');

        $has = false;
        /**
 * @var \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract $associatedProductAdapter
*/
        foreach ($associatedProductAdapters as $associatedProductAdapter) {
            if ($associatedProductAdapter->hasSpecialPrice($processRules, $product)) {
                $has = true;
                break;
            }
        }

        return $has;
    }

    /**
     * Map a column of an associated product through the feed's parent/associated inheritance rules.
     *
     * @param AdapterAbstract $adapter Associated product adapter
     * @param array<string,mixed> $column Column map entry
     * @return mixed False when no inheritance rule covers the column
     */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh -- legacy feed logic; refactor deferred to keep output identical
    protected function mapByInheritance(
        \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract $adapter,
        $column = []
    ) {
        if (array_key_exists('column', $column)) {
            $column_name = $column['column'];
            $map = $this->getAssociatedMapInheritance();

            foreach ($map as $row) {
                if ($row['column'] == $column_name) {
                    if (in_array($column['attribute'], $this->concatenateMapper->getDirectiveNames())
                        && !empty($row['extra'])
                    ) {
                        $attributes = explode(',', $row['extra']);
                        // for concatenation, map each attribute individually as defined in inheritance rule
                        foreach ($attributes as $code) {
                            $code = trim($code);
                            if (strpos($column['param'], $code) !== false) {
                                $val = $this->mapByInheritanceFrom(
                                    $adapter,
                                    ['column' => 'concat_'.$code, 'attribute' => $code],
                                    $row['from']
                                );
                                $column['param'] = str_replace("{{{$code}}}", $val, $column['param']);
                            }
                        }
                        // map column definition on associated product only,
                        return $adapter->getMapValue($column);
                    } else {
                        // map column definition as defined in inheritance rule
                        return $this->mapByInheritanceFrom($adapter, $column, $row['from']);
                    }
                }
            }
        }

        return false;
    }

    /**
     * Column value from the parent, the associated product, or the first non-empty of both.
     *
     * @param AdapterAbstract $adapter Associated product adapter
     * @param array<string,mixed> $column Column map entry
     * @param int|string $from Inheritance mode
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function mapByInheritanceFrom(
        \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract $adapter,
        $column = [],
        $from = Inheritance::ASSOCIATED_FIRST
    ) {
        switch ($from) {
            case Inheritance::PARENT_ONLY:
                $value = $this->getMapValue($column);
                break;
            case Inheritance::ASSOCIATED_ONLY:
                $value = $adapter->getMapValue($column);
                break;
            case Inheritance::PARENT_FIRST:
                $value = $this->getMapValue($column);
                if (empty($value)) {
                    $value = $adapter->getMapValue($column);
                }
                break;
            case Inheritance::ASSOCIATED_FIRST:
                $value = $adapter->getMapValue($column);
                if (empty($value)) {
                    $value = $this->getMapValue($column);
                }
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Unknown product inheritance mode.'));
        }
        return $value;
    }

    /**
     * Apply the "skip products with empty column" filters to a row and count a skip when one matches.
     *
     * @param  array $row
     * @return bool False when the row has a column the feed requires to be non-empty
     */
    protected function checkEmptyColumns($row)
    {
        $skipEmptyColumn = $this->feed->getConfig('filters_skip_column_empty');

        if (is_array($skipEmptyColumn)) {
            foreach ($skipEmptyColumn as $column) {
                if (isset($row[$column]) && $row[$column] == "") {
                    $this->getLogger()->info(
                        sprintf(
                            "product id %d skipped - by product skip rule, has %s empty.",
                            array_key_exists('id', $row) ? $row['id'] : $this->getProduct()->getId(),
                            $column
                        )
                    );
                    $this->getGenerator()->updateCountSkip(1);
                    return false;
                }
            }
        }

        return true;
    }
}
