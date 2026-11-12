<?php

namespace MageOS\ShoppingFeed\Test\Unit\Model;

use MageOS\ShoppingFeed\Test\Unit\CompatibilityTestCase;

class ModelFramework extends CompatibilityTestCase
{
    /****
     * A
     ****/
    /**
     * @var \MageOS\ShoppingFeed\Model\Generator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $generatorMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $adapterMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Adapter\Type\Configurable|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $parentAdapterMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\Product\Adapter\AdapterFactory
     */
    protected $adapterFactoryMock;

    /****
     * B
     ****/
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\Generator\Batch
     */
    protected $batchMock;

    /****
     * C
     ****/

    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\Cache|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cacheMock;

    /**
     * @var \Magento\Catalog\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $catalogHelperMock;

    /**
     * @var \Magento\Catalog\Model\Product\Type\Price|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $catalogProductPriceMock;

    /**
     * @var \Magento\CatalogRule\Model\Rule\Product\Price|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $catalogRuleMock;

    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $catalogRuleCollectionFactoryMock;

    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\Collection
     */
    protected $catalogRuleCollectionMock;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $categoryFactoryMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Category\CollectionProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $categoryCollectionProvider;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $categoryCollectionFactoryMock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $categoryCollectionMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\Config&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\ConfigFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configFactoryMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configCollectionMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configCollectionFactoryMock;

    /**
     * @var \Magento\Framework\Model\Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /****
     * D
     ****/
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $dateTimeMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateMock;

    /****
     * F
     ****/
    /**
     * @var \MageOS\ShoppingFeed\Model\Feed
     */
    protected $feed;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $feedMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\FeedTypes\Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $feedTypesConfigMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Framework\Filesystem\Driver\File
     */
    protected $fileDriverMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Filter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $filterMock;

    /****
     * H
     ****/
    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Helper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $helperMock;

    /****
     * I
     ****/
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Framework\Model\ResourceModel\Iterator
     */
    protected $iteratorMock;

    /****
     * L
     ****/
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\Logger
     */
    protected $loggerMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $localeDateMock;

    /****
     * M
     ****/
    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Mapper\MapperFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mapperFactoryMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Mapper\MapperAbstract|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mapperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\FeedFactory
     */
    protected $memoryMock;

    /****
     * O
     ****/
    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Processors\OptionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $optionFactoryMock;

    /**
     * @var \Magento\Catalog\Model\Product\Option|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $optionMock;

    /****
     * P
     ****/
    /**
     * @var \Magento\Directory\Model\PriceCurrency|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceCurrencyMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Catalog\Model\Product
     */
    protected $productMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Catalog\Model\ProductFactory
     */
    protected $productFactoryMock;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepositoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionProviderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $productCollectionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\Collection
     */
    protected $processCollectionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\CollectionFactory
     */
    protected $processCollectionFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\Generator\Process
     */
    protected $processMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\Generator\ProcessFactory
     */
    protected $processFactoryMock;

    /****
     * R
     ****/

    /**
     * @var \Magento\Framework\Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resource;

    /**
     * @var \Magento\Framework\Data\Collection\AbstractDb|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resourceCollection;

    /****
     * Q
     ****/
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\Generator\Queue
     */
    protected $queueMock;

    /****
     * S
     ****/
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\Schedule|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scheduleMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\ScheduleFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scheduleFactoryMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\ResourceModel\Feed\Schedule\Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scheduleCollectionMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\ResourceModel\Feed\Schedule\CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scheduleCollectionFactoryMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\Cache\ShippingProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingProviderMock;

    /**
     * @var \Magento\CatalogInventory\Model\Stock\Status|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $statusMock;

    /**
     * @var \Magento\CatalogInventory\Model\StockState|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $stockStateMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\CatalogInventory\Helper\Stock
     */
    protected $stockMock;

    /**
     * @var \Magento\Store\Model\Store|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeMock;

    /****
     * T
     ****/
    /**
     * @var \Magento\Tax\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxHelperMock;

    /****
     * U
     ****/
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\MageOS\ShoppingFeed\Model\ResourceModel\Feed\Upload\CollectionFactory
     */
    protected $uploadCollectionFactoryMock;

    /****
     * W
     ****/
    /**
     * @var \Magento\Weee\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $weeHelperMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->adapterMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple',
            ['getProduct', 'getData', 'getFilter', 'getFeed', 'getMapAttribute', 'getAttributeValue',
                'isExcludedByConditions', 'getMapValue', 'mapEmptyValues', 'getTimezone', 'getStore', 'getPrices',
                'getOptionProcessor', 'getOptions', 'hasParentAdapter', 'getParentAdapter',
                'getInventoryCount', 'getSalePriceEffectiveDates', 'hasSpecialPrice', 'getUrlOptions',
                'isSkipped', 'isDuplicate', 'map', 'setTestMode', 'getConfig', 'getGenerator', 'getSkipProduct',
                'getSkipMessage', 'setSkipProduct']
        );
        $this->expectSelf($this->adapterMock, 'getParentAdapter');
        $this->expectSelf($this->adapterMock, 'setSkipProduct');

        $this->generatorMock = $this->getModelMock('MageOS\ShoppingFeed\Model\Generator', ['updateCountSkip']);

        $this->adapterFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Adapter\AdapterFactory',
            ['create', 'map', 'getSkipProduct', 'getSkipMessage']
        );

        $this->batchMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Generator\Batch',
            ['getOffset', 'setOffset', 'isEnabled']
        );

        $this->cacheMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Generator\Cache',
            ['getCache', 'setCache']
        );

        $this->catalogHelperMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Helper\Catalog',
            ['getTaxPrice']
        );

        $this->catalogProductPriceMock = $this->getModelMock(
            'Magento\Catalog\Model\Product\Type\Price',
            ['calculatePrice']
        );

        $this->catalogRuleCollectionFactoryMock = $this->getModelMock(
            'Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\CollectionFactory',
            ['create']
        );
        $this->catalogRuleCollectionMock = $this->getModelMock(
            'Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\Collection',
            ['addFieldToFilter', 'addFieldToSelect', 'getFirstItem']
        );
        $this->catalogRuleMock = $this->getModelMock(
            'Magento\CatalogRule\Model\Rule\Product\Price',
            ['getData']
        );
        $this->expectReturn($this->catalogRuleCollectionFactoryMock, 'create', $this->catalogRuleCollectionMock);
        $this->expectReturn($this->catalogRuleCollectionMock, 'getFirstItem', $this->catalogRuleMock);
        $this->expectSelf($this->catalogRuleCollectionMock, ['addFieldToFilter', 'addFieldToSelect']);

        $this->categoryFactoryMock = $this->getModelMock(
            'Magento\Catalog\Model\CategoryFactory',
            ['create','load', 'setStoreId', 'getName']
        );

        $this->categoryCollectionProvider = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Category\CollectionProvider',
            ['getCategories', 'addFieldToFilter', 'exportToArray', 'addAttributeToSelect', 'addIsActiveFilter',
            'setStoreId', 'addPathFilter', 'addLevelFilter', 'addAttributeToSort']
        );

        $this->categoryCollectionFactoryMock = $this->getModelMock(
            'Magento\Catalog\Model\ResourceModel\Category\CollectionFactory',
            ['create']
        );

        $this->expectSelf($this->categoryCollectionProvider, 'addAttributeToSelect');
        $this->expectSelf($this->categoryCollectionProvider, 'setStoreId');
        $this->expectSelf($this->categoryCollectionProvider, 'addPathFilter');
        $this->expectSelf($this->categoryCollectionProvider, 'addLevelFilter');
        $this->expectSelf($this->categoryCollectionProvider, 'addIsActiveFilter');
        $this->expectSelf($this->categoryCollectionProvider, 'addFieldToFilter');

        $this->configMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Feed\Config',
            ['getData', 'setData', 'save']
        );

        $this->configFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Feed\ConfigFactory',
            ['create']
        );
        $this->expectReturn($this->configFactoryMock, 'create', $this->configMock);

        $this->configCollectionFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\CollectionFactory',
            ['create']
        );

        $this->dateMock = $this->getModelMock('Magento\Framework\Stdlib\DateTime', ['date', 'isEmptyDate']);

        $this->dateTimeMock = $this->getModelMock(
            'Magento\Framework\Stdlib\DateTime\Timezone',
            ['date', 'getTimestamp', 'formatDate', 'formatDateTime', 'getConfigTimezone', 'timestamp']
        );

        $this->feedMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Feed',
            ['getId', 'getConfig', 'getFile', 'getColumnsMap', 'getData', 'hasData', 'setData', 'saveMessages',
                'saveStatus', 'getStore', 'getUploadCollection', 'getType', 'getStoreId', 'getSchedules']
        );

        $this->feedTypesConfigMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\FeedTypes\Config',
            ['getFeed', 'isAllowedDirective', 'getDirective']
        );

        $this->fileDriverMock = $this->getModelMock(
            'Magento\Framework\Filesystem\Driver\File',
            ['fileOpen', 'fileWrite', 'fileClose', 'rename', 'isExists', 'createDirectory']
        );

        $this->filterMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Filter',
            ['findAndReplace', 'cleanField']
        );
        $this->expectSelf($this->filterMock, ['findAndReplace']);
        $this->expectAdvencedReturn($this->filterMock, 'cleanField', $this->returnArgument(0));
        $this->expectReturn($this->adapterMock, 'getFilter', $this->filterMock);

        $this->helperMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Helper',
            ['getQuantityIcrements', 'hasMsrp']
        );

        $this->iteratorMock = $this->getModelMock(
            'Magento\Framework\Model\ResourceModel\Iterator',
            ['walk']
        );

        $this->loggerMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Logger',
            ['info', 'notice', 'warning', 'debug', 'addHandler', 'getHandlers']
        );

        $this->priceCurrencyMock = $this->getModelMock(
            'Magento\Directory\Model\PriceCurrency',
            ['getCurrency']
        );

        $this->memoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Generator\Memory',
            ['isCloseToPhpLimit']
        );

        $this->mapperFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Mapper\MapperFactory',
            ['create', 'getMapperData']
        );

        $this->mapperMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Mapper\MapperAbstract',
            ['map', 'format', 'filter', 'addAdapter', 'popAdapter']
        );
        $this->expectAdvencedReturn($this->mapperMock, 'format', $this->returnArgument(0));

        $this->optionFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Processors\OptionFactory',
            ['create']
        );

        $this->optionMock = $this->getModelMock(
            'Magento\Catalog\Model\Product\Option',
            ['getTitle', 'getId', 'getValues', 'getGroupByType']
        );

        $optionProcessorMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Processors\Option',
            ['setAdapter', 'execute']
        );
        $this->expectSelf($optionProcessorMock, 'setAdapter');
        $this->expectAdvencedReturn($optionProcessorMock, 'execute', $this->returnArgument(0));
        $this->expectReturn($this->optionFactoryMock, 'create', $optionProcessorMock);

        $this->productMock = $this->getModelMock(
            'Magento\Catalog\Model\Product',
            ['getId', 'getMediaGalleryImages', 'hasData', 'getData', 'toFlatArray', 'isInStock',
                'getCategoryIds', 'getProductUrl', 'getStore', 'getCategoryCollection', 'getSku',
                'getResource', 'getPrice', 'getSpecialPrice', 'getFinalPrice', 'getSpecialFromDate',
                'getSpecialToDate', 'getQty', 'getTypeInstance', 'getUsedProductCollection',
                'addAttributeToSelect', 'setFlag', 'getConfigurableAttributes', 'getOptionsIds',
                'getSelectionsCollection', 'getPriceModel', 'setData', 'setSpecialPrice', 'setFinalPrice',
                'getTotalPrices', 'getAssociatedProductCollection', 'getLinkInstance', 'setLinkModel',
                'setIsStrongMode',
                'setProduct', 'addFilterByRequiredOptions', 'setPositionOrder',
                'addStoreFilter', 'getOptions', 'hasSpecialPrice', 'calculatePrice', 'setStoreId', 'getStoreId',
                'getPriceInfo', 'getTierPrice', 'load']
        );
        $this->expectSelf(
            $this->productMock,
            ['setData', 'setSpecialPrice', 'setFinalPrice', 'getPriceModel', 'setStoreId', 'load']
        );

        $this->productFactoryMock = $this->getModelMock(
            'Magento\Catalog\Model\ProductFactory',
            ['create', 'load', 'setStoreId']
        );

        $this->productRepositoryMock = $this->getModelMock(
            'Magento\Catalog\Api\ProductRepositoryInterface',
            ['save', 'get', 'getById', 'delete', 'deleteById', 'getList']
        );
        $this->expectReturn($this->productRepositoryMock, 'getById', $this->productMock);

        $this->productCollectionProviderMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\CollectionProvider',
            ['create', 'setStoreId', 'addStoreFilter', 'addAttributeToFilter',
                'getSelect', 'group', 'limit', 'getCollection', 'reset', 'getSize']
        );

        $this->productCollectionMock = $this->getModelMock(
            'Magento\Catalog\Model\ResourceModel\Product\Collection',
            ['setStoreId', 'addStoreFilter', 'addAttributeToFilter', 'getSelect', 'group', 'limit',
                'getPart', 'setPart', 'joinInner', 'getConnection', 'quoteInto', 'where', 'getTable',
                'addPriceData', 'reset', 'getSize']
        );

        $this->processMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Generator\Process',
            ['load', 'getId', 'setParentItemId', 'getStatus', 'getParentItemId',
            'setFeedId', 'setItemId', 'setStatus', 'save']
        );
        $this->expectSelf(
            $this->processMock,
            [
            'load', 'setParentItemId',
            'setFeedId', 'setItemId', 'setStatus', 'save'
            ]
        );

        $this->processFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Generator\ProcessFactory',
            ['create']
        );
        $this->expectReturn($this->processFactoryMock, 'create', $this->processMock);

        $this->processCollectionMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\Collection',
            ['load', 'truncate', 'setFeedFilter', 'setProductFilter']
        );
        $this->expectSelf($this->processCollectionMock, ['truncate', 'setFeedFilter', 'setProductFilter']);

        $this->processCollectionFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\CollectionFactory',
            ['create', 'truncate']
        );
        $this->expectSelf($this->processCollectionFactoryMock, ['truncate']);
        $this->expectReturn($this->processCollectionFactoryMock, 'create', $this->processCollectionMock);

        $this->queueMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Generator\Queue',
            ['getBatch', 'setBatch', 'setData', 'save', 'getId', 'delete', 'isEnabled']
        );

        $this->scopeConfigMock = $this->getModelMock(
            'Magento\Framework\App\Config',
            ['getValue', 'isSetFlag']
        );

        /**
 * Schedule Mocks
*/
        $this->scheduleMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Feed\Schedule',
            ['getId', 'getData', 'getFormattedSchedule', 'load', 'save', 'delete']
        );

        $this->scheduleFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Feed\ScheduleFactory',
            ['create']
        );
        $this->expectReturn($this->scheduleFactoryMock, 'create', $this->scheduleMock);
        $this->expectSelf($this->scheduleMock, 'load');

        $this->scheduleCollectionFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\ResourceModel\Feed\Schedule\CollectionFactory',
            ['create']
        );

        $this->shippingProviderMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Generator\Cache\ShippingProvider',
            ['clearCache', 'prepareCache', 'getCache', 'setCache', 'getShipping']
        );

        $this->uploadCollectionFactoryMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\ResourceModel\Feed\Upload\CollectionFactory',
            ['create', 'setFeedFilter']
        );
        $this->expectSelf($this->uploadCollectionFactoryMock, ['create', 'setFeedFilter']);

        $this->taxHelperMock = $this->getModelMock('Magento\Tax\Helper\Data', ['getConfig']);

        $this->weeHelperMock = $this->getModelMock(
            'Magento\Weee\Helper\Data',
            ['getAmountExclTax', 'isTaxable', 'getProductWeeeAttributesForRenderer']
        );

        /****
 * SYSTEM MOCKS
****/
        $eventManager = $this->getMockBuilder('Magento\Framework\Event\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $eventManager->expects($this->any())
            ->method('dispatch')
            ->will($this->returnSelf());

        $this->contextMock = $this->getMockBuilder('Magento\Framework\Model\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->expectReturn($this->contextMock, 'getEventDispatcher', $eventManager);

        $this->registryMock = $this->getMockBuilder('Magento\Framework\Registry')->getMock();
        $this->resource = $this->getMockBuilder('Magento\Review\Model\ResourceModel\Review')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceCollection = $this->getMockBuilder('Magento\Framework\Data\Collection\AbstractDb')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeMock = $this->getModelMock(
            'Magento\Store\Model\Store',
            ['getStoreId', 'load', 'getCode', 'getCurrentCurrency',
                'getBaseUrl', 'getBaseCurrency', 'convert', 'getRootCategoryId']
        );
        $this->expectReturn($this->adapterMock, 'getStore', $this->storeMock);

        $this->statusMock = $this->getModelMock(
            'Magento\CatalogInventory\Model\Stock\Status',
            ['load', 'getStockStatus', 'getStockQty', 'getQty']
        );
        $this->stockStateMock = $this->getModelMock('Magento\CatalogInventory\Model\StockState', ['getStockQty']);
        $this->expectSelf($this->statusMock, 'load');
        $this->expectReturn($this->statusMock, 'getQty', 1);

        /**
 * @var \DateTime&\PHPUnit\Framework\MockObject\MockObject $datetimeMock
*/
        $datetimeMock = $this->getMockBuilder('\DateTime')
            ->disableOriginalConstructor()
            ->getMock();
        $datetimeMock->expects($this->any())
            ->method('format')
            ->will($this->returnValue(date('Y-m-d H:i:s')));
        $datetimeMock->method('modify')->willReturnSelf();

        $this->localeDateMock = $this->getModelMock(
            '\Magento\Framework\Stdlib\DateTime\TimezoneInterface'
        );
        $this->localeDateMock->expects($this->any())
            ->method('date')
            ->will($this->returnValue($datetimeMock));
    }

    /**
     * @template T of object
     * @param  class-string<T> $className
     * @param  list<string>    $methods
     * @return T&\PHPUnit\Framework\MockObject\MockObject
     */
    public function getModelMock($className, array $methods = [])
    {
        return $this->createCompatibleMock($className, $methods);
    }

    /**
     * Advanced return expection method, passing Stub object
     *
     * @param \PHPUnit\Framework\MockObject\MockObject $mock
     * @param string                                   $method
     * @param \PHPUnit\Framework\MockObject\Stub\Stub  $return
     */
    public function expectAdvencedReturn($mock, $method, $return)
    {
        $mock->expects($this->any())
            ->method($method)
            ->will($return);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $mock
     * @param string                                   $method
     * @param mixed                                    $return
     */
    public function expectReturn($mock, $method, $return)
    {
        $mock->expects($this->any())
            ->method($method)
            ->will($this->returnValue($return));
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $mock
     * @param string|string[]                          $methods
     */
    public function expectSelf($mock, $methods)
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }

        foreach ($methods as $method) {
            $mock->expects($this->any())
                ->method($method)
                ->will($this->returnSelf());
        }
    }

    public function setExpectedException($type = 'MageOS\ShoppingFeed\Model\Exception', $msg = '')
    {
        $this->expectException($type);
    }
}
