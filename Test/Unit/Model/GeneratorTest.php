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


namespace MageOS\ShoppingFeed\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class GeneratorTest
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class GeneratorTest extends ModelFramework
{
    /**
     * @var \MageOS\ShoppingFeed\Model\Generator|null
     */
    protected $model;

    /**
     * @var array
     */
    protected $objectData = [];

    public function setUp(): void
    {
        global $setGlobalMockFunctionLimit;
        parent::setUp();

        $this->expectSelf($this->dateTimeMock, 'date');
        $this->expectSelf($this->feedMock, ['saveMessages', 'saveStatus']);

        $this->objectData = [
            'productRepositoryInterface'    => $this->productRepositoryMock,
            'fileDriver'        => $this->fileDriverMock,
            'iterator'          => $this->iteratorMock,
            'dateTime'          => $this->dateTimeMock,
            'collectionProvider'=> $this->productCollectionProviderMock,
            'logger'            => $this->loggerMock,
            'adapterFactory'    => $this->adapterFactoryMock,
            'batch'             => $this->batchMock,
            'memory'            => $this->memoryMock,
            'processCollectionFactory' => $this->processCollectionFactoryMock,
            'queue'             => $this->queueMock,
            'feed'              => $this->feedMock,
            'productFactory'    => $this->productFactoryMock,
            'scheduleFactory'   => $this->scheduleFactoryMock
        ];

        $setGlobalMockFunctionLimit = false;
    }

    protected function createModel()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->model = $this->objectManagerHelper->getObject(
            'MageOS\ShoppingFeed\Model\Generator',
            $this->objectData
        );
    }

    public function testSelectionFailureBindsTheFeedLoggerBeforeValidation(): void
    {
        $feed = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Feed::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData('id', 123);
        $logger = $this->createMock(\MageOS\ShoppingFeed\Model\Logger::class);
        $selection = $this->createMock(\MageOS\ShoppingFeed\Model\ProductSelection\Selection::class);
        $exception = new \Magento\Framework\Exception\LocalizedException(__('Invalid rule'));
        $selection->method('validate')->willThrowException($exception);
        $selection->method('beginRun')->willThrowException($exception);
        $generator = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Generator::class)
            ->disableOriginalConstructor()->onlyMethods(['getLogger', 'updateStatus'])->getMock();
        foreach (['feed' => $feed, 'logger' => $logger, 'productSelection' => $selection] as $name => $value) {
            (new \ReflectionProperty(\MageOS\ShoppingFeed\Model\Generator::class, $name))
                ->setValue($generator, $value);
        }
        $generator->expects(self::once())->method('getLogger')->willReturn($logger);
        $logger->expects(self::once())->method('error')->with('Invalid rule');
        $generator->expects(self::once())->method('updateStatus')->with(5);
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $generator->run();
    }

    public function testRepeatedSelectionChecksCountOneCandidatePerParentContext(): void
    {
        $generator = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Generator::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $generator->addSelectionExclusion(7);
        $generator->addSelectionExclusion(7);
        $generator->addSelectionExclusion(7, 10);
        $generator->addSelectionExclusion(7, 10);
        $generator->addSelectionExclusion(7, 20);
        self::assertSame(3, $generator->getCountProductsSkipped());
        $logged = (new \ReflectionProperty($generator, 'skippedData'))->getValue($generator);
        self::assertCount(3, $logged);
        foreach ($logged as $ids) {
            self::assertSame([7], $ids);
        }
    }

    /** @dataProvider temporaryFileEncodingCases */
    #[\PHPUnit\Framework\Attributes\DataProvider('temporaryFileEncodingCases')]
    public function testTemporaryFileEncoding(string $type, bool $batchEnabled, bool $newBatch, bool $expectBom): void
    {
        $this->feedMock->method('getData')->willReturn($type);
        $this->objectData['batch'] = new \MageOS\ShoppingFeed\Model\Generator\Batch([
            'enabled' => $batchEnabled,
            'offset' => $newBatch ? 0 : 100,
        ]);
        $mode = !$batchEnabled || $newBatch ? 'w' : 'a';
        $this->fileDriverMock->expects($this->once())->method('fileOpen')
            ->with('/tmp/feed.txt.tmp', $mode)->willReturn('handle');
        $this->fileDriverMock->expects($expectBom ? $this->once() : $this->never())->method('fileWrite')
            ->with('handle', \MageOS\ShoppingFeed\Model\Generator::UTF8_BOM);
        $this->createModel();
        $this->model->setData('feed_file', '/tmp/feed.txt');
        $method = new \ReflectionMethod($this->model, 'getTemporaryHandle');
        $firstHandle = $method->invoke($this->model);
        $this->assertSame('handle', $firstHandle);
        $secondHandle = $method->invoke($this->model);
        $this->assertSame('handle', $secondHandle);
    }

    public static function temporaryFileEncodingCases(): array
    {
        return [
            'Microsoft full feed' => ['microsoft_merchant_center', false, false, false],
            'Microsoft first batch' => ['microsoft_merchant_center', true, true, false],
            'Microsoft later batch' => ['microsoft_merchant_center', true, false, false],
            'Google full feed' => ['google_shopping', false, false, true],
            'Google first batch' => ['google_shopping', true, true, true],
            'Google later batch' => ['google_shopping', true, false, false],
            'Generic full feed' => ['generic', false, false, true],
        ];
    }

    public function testConfiguredEnclosureEscapeAndDefaultValueAreUsed(): void
    {
        $settings = [
            'output_params_delimiter' => '|',
            'output_params_enclose_cell' => '"',
            'output_params_enclose_escape' => '"',
            'output_params_default_value' => 'missing',
        ];
        $this->feedMock->method('getConfig')->willReturnCallback(
            static fn($key, $default = '') => $settings[$key] ?? $default
        );
        $this->feedMock->method('getColumnsMap')->willReturn([
            ['column' => 'title'], ['column' => 'empty'], ['column' => 'zero'],
        ]);
        $this->fileDriverMock->expects($this->once())->method('fileWrite')
            ->with('handle', PHP_EOL . '"A|""B"""|"missing"|"0"');
        $this->createModel();
        $this->model->setData('temporary_handle', 'handle');
        (new \ReflectionMethod($this->model, 'writeFeed'))->invoke(
            $this->model,
            ['title' => 'A|"B"', 'empty' => '', 'zero' => 0]
        );
    }

    public function testDestructionDoesNotCommitPartialBatchProgress(): void
    {
        $generator = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Generator::class)
            ->disableOriginalConstructor()->onlyMethods(['updateBatchQueue'])->getMock();
        (new \ReflectionProperty($generator, 'batch'))->setValue(
            $generator,
            new \MageOS\ShoppingFeed\Model\Generator\Batch(['enabled' => true, 'offset' => 100])
        );
        $generator->expects($this->never())->method('updateBatchQueue');
        try {
            $generator->__destruct();
        } finally {
            (new \ReflectionProperty($generator, 'batch'))->setValue($generator, null);
        }
    }

    public function testWriteFeedKeepsGoogleRowsAlignedWithoutTrailingTabs(): void
    {
        $this->expectReturn($this->feedMock, 'getData', 'google_shopping');
        $this->feedMock->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(static function ($key, $default = '') {
                return $key === 'output_params_delimiter' ? '\\t' : $default;
            });
        $this->feedMock->expects($this->once())
            ->method('getColumnsMap')
            ->willReturn([
                ['column' => 'id'],
                ['column' => 'promotion_id'],
            ]);
        $this->fileDriverMock->expects($this->once())
            ->method('fileWrite')
            ->with('handle', PHP_EOL . "\t1");

        $this->createModel();
        $this->model->setData('temporary_handle', 'handle');

        $method = new \ReflectionMethod($this->model, 'writeFeed');
        $method->invoke($this->model, ['id' => '1']);
    }

    public function testWriteFeedPreservesGoogleColumnNames(): void
    {
        $this->expectReturn($this->feedMock, 'getData', 'google_shopping');
        $this->feedMock->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(static function ($key, $default = '') {
                return $key === 'output_params_delimiter' ? '\\t' : $default;
            });
        $this->feedMock->expects($this->once())
            ->method('getColumnsMap')
            ->willReturn([
                ['column' => 'id'],
                ['column' => 'price'],
                ['column' => 'sale_price'],
                ['column' => 'sale_price_effective_date'],
            ]);
        $this->fileDriverMock->expects($this->once())
            ->method('fileWrite')
            ->with('handle', "price\tsale_price\tsale_price_effective_date\tid");

        $this->createModel();
        $this->model->setData('temporary_handle', 'handle');

        $method = new \ReflectionMethod($this->model, 'writeFeed');
        $method->invoke(
            $this->model,
            [
                'id' => 'id',
                'price' => 'price',
                'sale_price' => 'sale_price',
                'sale_price_effective_date' => 'sale_price_effective_date',
            ],
            false
        );
    }

    public function testWriteFeedOmitsSalePriceWhenItIsNotLowerThanPrice(): void
    {
        $this->expectReturn($this->feedMock, 'getData', 'google_shopping');
        $this->feedMock->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(static function ($key, $default = '') {
                return $key === 'output_params_delimiter' ? '\\t' : $default;
            });
        $this->feedMock->expects($this->once())
            ->method('getColumnsMap')
            ->willReturn([
                ['column' => 'price'],
                ['column' => 'sale_price'],
                ['column' => 'sale_price_effective_date'],
                ['column' => 'id'],
            ]);
        $this->fileDriverMock->expects($this->once())
            ->method('fileWrite')
            ->with('handle', PHP_EOL . "10.00 EUR\t\t\t72");

        $this->createModel();
        $this->model->setData('temporary_handle', 'handle');

        $method = new \ReflectionMethod($this->model, 'writeFeed');
        $method->invoke(
            $this->model,
            [
                'price' => '10.00 EUR',
                'sale_price' => '10.00 EUR',
                'sale_price_effective_date' => '2026-08-18T00:00:00+00:00/2027-08-18T00:00:00+00:00',
                'id' => '72',
            ]
        );
    }

    public function testGetTotalItems()
    {
        $this->expectReturn($this->dateTimeMock, 'timestamp', 1000000);

        $this->expectSelf($this->productCollectionMock, ['setStoreId', 'addStoreFilter', 'getSelect', 'getConnection']);
        $this->expectReturn($this->productCollectionMock, 'getPart', ['cat_product' => []]);
        $this->expectReturn($this->productCollectionMock, 'getSize', 5);

        $this->expectReturn($this->productCollectionProviderMock, 'getCollection', $this->productCollectionMock);

        $this->expectReturn($this->feedMock, 'getId', 1);

        $this->feedMock->expects($this->any())
            ->method('getConfig')
            ->will($this->returnCallback(function ($key, $default = '') {
                switch ($key) {
                    case 'filters_product_types':
                        return ['simple','configurable','bundle','virtual','downloadable'];
                    case 'filters_add_out_of_stock':
                        return false;
                    case 'categories_include_all_products':
                        return true;
                    case 'filters_attribute_sets':
                        return ['', 'attribute_set'];
                    case 'categories_provider_taxonomy_by_category':
                        return [['category' => 5, 'disabled' => true]];
                    default:
                        break;
                }
                return $default;
            }));

        $this->createModel();
        $this->model->setTestSku('testSku');

        $this->assertEquals(5, $this->model->getTotalItems());
    }

    public function testGetCollection()
    {
        $this->expectReturn($this->dateTimeMock, 'timestamp', 1000000);

        $this->expectSelf($this->productCollectionMock, ['setStoreId', 'addStoreFilter', 'getSelect', 'getConnection']);
        $this->expectReturn($this->productCollectionMock, 'getPart', ['cat_product' => []]);
        $this->expectReturn($this->productCollectionMock, 'getSize', 5);

        $this->expectReturn($this->productCollectionProviderMock, 'getCollection', $this->productCollectionMock);
        $this->expectReturn($this->batchMock, 'isEnabled', true);
        $this->expectReturn($this->queueMock, 'getBatch', $this->batchMock);

        $this->createModel();
        $this->model->setTestSku('testSku');

        $this->assertInstanceOf(
            'Magento\Catalog\Model\ResourceModel\Product\Collection',
            $this->model->getCollection()
        );
    }

    public function testProcessProductCallbackSkippedPhpLimit()
    {
        $args = [
            'row' => [
                'type_id' => 'simple',
                'entity_id' => 1,
                'sku' => 'fakeSku'
            ]
        ];

        $this->setProcessProductCallbackMocks();
        $this->expectReturn($this->queueMock, 'isEnabled', false);
        $this->expectReturn($this->dateTimeMock, 'getTimestamp', 1000);
        $this->expectReturn($this->dateTimeMock, 'formatDate', '01-01-2010');
        $this->expectReturn($this->memoryMock, 'isCloseToPhpLimit', true);
        $this->expectReturn($this->productFactoryMock, 'create', $this->productMock);

        $this->expectReturn($this->feedMock, 'getSchedules', [0 => ['id' => 1]]);
        $this->expectReturn($this->scheduleFactoryMock, 'create', $this->scheduleMock);

        $this->createModel();
        $this->model->setData('total_items', 100);

        $this->setExpectedException('Exception');

        $this->model->processProductCallback($args);
    }

    public function testProcessProductCallbackSkipped()
    {
        $args = [
            'row' => [
                'type_id' => 'simple',
                'entity_id' => 1,
                'sku' => 'fakeSku'
            ]
        ];

        $this->setProcessProductCallbackMocks();
        $this->expectReturn($this->dateTimeMock, 'formatDateTime', '01-01-2010');

        $this->createModel();
        $this->model->setData('total_items', 100);

        $this->assertEquals(null, $this->model->processProductCallback($args));
    }

    protected function setProcessProductCallbackMocks()
    {
        $this->feedMock->expects($this->any())
            ->method('getConfig')
            ->will($this->returnCallback(static function ($key, $default = '') {
                return $key === 'filters_product_types' ? ['simple'] : $default;
            }));
        $this->expectReturn($this->feedMock, 'getId', 1);

        $this->expectReturn($this->productFactoryMock, 'create', $this->productMock);
        $this->expectReturn($this->productFactoryMock, 'load', $this->productMock);

        $this->expectReturn($this->adapterFactoryMock, 'create', $this->adapterMock);
        $this->expectReturn($this->adapterMock, 'isSkipped', false);
        $this->expectReturn($this->adapterMock, 'map', []);
        $this->expectReturn($this->adapterMock, 'getSkipProduct', true);
        $this->expectReturn($this->adapterMock, 'getSkipMessage', 'Skip message');

        $this->expectReturn($this->queueMock, 'getBatch', $this->batchMock);
        $this->expectReturn($this->queueMock, 'getId', 1);
        $this->expectSelf($this->queueMock, ['setBatch', 'setData', 'save']);

        $this->expectSelf($this->productMock, 'load');
    }

    public function testProcessProductCallback()
    {
        $args = [
            'row' => [
                'type_id' => 'simple',
                'entity_id' => 1,
                'sku' => 'fakeSku'
            ]
        ];

        $this->feedMock->expects($this->any())
            ->method('getConfig')
            ->will($this->returnCallback(static function ($key, $default = '') {
                return $key === 'filters_product_types' ? ['simple'] : $default;
            }));
        $this->expectReturn($this->feedMock, 'getId', 1);
        $this->expectReturn($this->feedMock, 'getData', [
            'cell_enclose' => '"',
            'cell_enclose_escape' => '\"'
        ]);
        $this->expectReturn($this->feedMock, 'getColumnsMap', [
            ['column' => 'columnName'],
            ['column' => 'columnEmptyName']
        ]);

        $this->expectReturn($this->productFactoryMock, 'create', $this->productMock);
        $this->expectReturn($this->productFactoryMock, 'load', $this->productMock);

        $this->expectReturn($this->adapterFactoryMock, 'create', $this->adapterMock);
        $this->expectReturn($this->adapterMock, 'isSkipped', false);
        $this->expectReturn($this->adapterMock, 'map', [['columnName' => 'columnValue']]);
        $this->expectReturn($this->adapterMock, 'getSkipProduct', false);

        $this->expectReturn($this->queueMock, 'getBatch', $this->batchMock);
        $this->expectSelf($this->queueMock, ['setBatch', 'setData', 'save']);

        $this->setProcessProductCallbackMocks();

        $this->createModel();
        $this->model->setData('total_items', 100);

        $this->assertEquals(null, $this->model->processProductCallback($args));
    }

    public function testProcessProductCallbackAdapterError()
    {
        $args = [
            'row' => [
                'type_id' => 'simple',
                'entity_id' => 1,
                'sku' => 'fakeSku'
            ]
        ];

        $this->feedMock->expects($this->any())->method('getConfig')
            ->will($this->returnCallback(static function ($key, $default = '') {
                return $key === 'filters_product_types' ? ['simple'] : $default;
            }));

        $this->expectReturn($this->feedMock, 'getId', 1);
        $this->expectReturn($this->productMock, 'toFlatArray', []);
        $this->expectSelf($this->productMock, 'load');
        $this->expectReturn($this->productFactoryMock, 'create', $this->productMock);
        $this->expectReturn($this->productFactoryMock, 'load', $this->productMock);
        $this->expectReturn($this->adapterFactoryMock, 'create', false);

        $this->createModel();
        $this->model->setData('total_items', 100);

        $this->assertEquals(false, $this->model->processProductCallback($args));
    }

    public function testProcessProductCallbackProductTypeError()
    {
        $args = [
            'row' => [
                'type_id' => 'unknown',
            ]
        ];

        $this->expectReturn($this->feedMock, 'getConfig', ['simple']);
        $this->expectReturn($this->feedMock, 'getId', 1);

        $this->createModel();

        $this->assertEquals(false, $this->model->processProductCallback($args));
    }

    public function testProcessProductCallbackTestMode()
    {
        $args = [
            'row' => [
                'type_id' => 'simple',
                'entity_id' => 1,
                'sku' => 'fakeSku'
            ]
        ];

        $this->feedMock->expects($this->any())
            ->method('getConfig')
            ->will($this->returnCallback(static function ($key, $default = '') {
                return $key === 'filters_product_types' ? ['simple'] : $default;
            }));
        $this->expectReturn($this->feedMock, 'getId', 1);
        $this->expectReturn($this->feedMock, 'getData', [
            'cell_enclose' => '"',
            'cell_enclose_escape' => '\"'
        ]);
        $this->expectReturn($this->feedMock, 'getColumnsMap', [
            ['column' => 'columnName'],
            ['column' => 'columnEmptyName']
        ]);

        $this->expectReturn($this->productFactoryMock, 'create', $this->productMock);
        $this->expectReturn($this->productFactoryMock, 'load', $this->productMock);
        $this->expectSelf($this->productMock, 'load');

        $this->expectReturn($this->adapterFactoryMock, 'create', $this->adapterMock);
        $this->expectReturn($this->adapterMock, 'isSkipped', false);
        $this->expectReturn($this->adapterMock, 'map', [['columnName' => 'columnValue']]);
        $this->expectReturn($this->adapterMock, 'getSkipProduct', false);

        $this->expectReturn($this->queueMock, 'getBatch', $this->batchMock);
        $this->expectSelf($this->queueMock, ['setBatch', 'setData', 'save']);

        $this->createModel();
        $this->model->setData('total_items', 100);
        $this->model->setTestSku('test-sku');
        $this->model->processProductCallback($args);

        $output = $this->model->getTestOutput();
        $expected = [[
            [
                'label' => 'columnName',
                'value' => 'columnValue'
            ],[
                'label' => 'columnEmptyName',
                'value' => null
            ]
        ]];

        $this->assertEquals($expected, $output);
    }

    public function testRunWithBatchFinished()
    {
        $this->setTestRunMocks();
        $this->expectReturn($this->feedMock, 'getId', 1);

        $this->expectReturn($this->batchMock, 'isEnabled', true);
        $this->expectReturn($this->batchMock, 'getOffset', 20);

        $this->expectReturn($this->queueMock, 'getBatch', $this->batchMock);
        $this->expectReturn($this->queueMock, 'getId', 5);

        $this->objectData['queue'] = $this->queueMock;

        $this->createModel();
        $this->model->setTestSku('testSku');
        $this->model->setData('total_items', 10);

        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Generator', $this->model->run());
        $this->assertEquals([], $this->model->getTestOutput());
    }

    public function testRunWithBatch()
    {
        $this->setTestRunMocks();
        $this->expectReturn($this->feedMock, 'getId', 1);
        $this->expectReturn($this->batchMock, 'isEnabled', true);

        $this->createModel();
        $this->model->setData('total_items', 10);

        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Generator', $this->model->run());
    }

    public function testRunHeader()
    {
        $this->expectReturn($this->feedMock, 'getColumnsMap', [
            ['column' => 'columnName'],
            ['column' => 'columnName'],
            ['column' => 'columnName']
        ]);
        $this->setTestRunMocks();
        $this->expectReturn($this->feedMock, 'getId', 1);
        $this->expectReturn($this->batchMock, 'isEnabled', true);

        $this->createModel();
        $this->model->setData('total_items', 10);

        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Generator', $this->model->run());
    }

    public function testRun()
    {
        $this->setTestRunMocks();
        $this->expectReturn($this->fileDriverMock, 'isExists', false);
        $this->expectReturn($this->feedMock, 'getId', 1);
        $this->expectReturn($this->feedMock, 'getUploadCollection', []);
        $this->expectReturn($this->batchMock, 'isEnabled', false);

        $this->objectData['queue'] = $this->queueMock;
        $this->createModel();

        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Generator', $this->model->run());
    }

    /** @dataProvider invalidRotationLimits */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidRotationLimits')]
    public function testInvalidLogRotationSettingDoesNotAbortGeneration($limit): void
    {
        $this->setTestRunMocks();
        $this->feedMock->method('getId')->willReturn(1);
        $this->feedMock->method('getUploadCollection')->willReturn([]);
        $config = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $config->method('getValue')->willReturn($limit);
        $directory = $this->createMock(\Magento\Framework\App\Filesystem\DirectoryList::class);
        $directory->method('getRoot')->willReturn('');
        $this->objectData['scopeConfig'] = $config;
        $this->objectData['directoryList'] = $directory;
        $this->createModel();
        $path = tempnam(sys_get_temp_dir(), 'feed-log-');
        file_put_contents($path, 'original log');
        $this->model->setData('feed_log_file', $path);
        try {
            self::assertSame($this->model, $this->model->run());
            self::assertSame('original log', file_get_contents($path));
        } finally {
            unlink($path);
            foreach (glob($path . '.*.gz') as $archive) {
                unlink($archive);
            }
        }
    }

    public static function invalidRotationLimits(): array
    {
        return [['abc'], [''], [null], [-1], [0], [[]]];
    }

    public function testProcessUploadsCompressesAndRemovesGzipArtifact()
    {
        $upload = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Feed\Upload',
            ['getGzip', 'getHost', 'getPath']
        );
        $this->expectReturn($upload, 'getGzip', 1);
        $this->expectReturn($upload, 'getHost', 'merchant.example');
        $this->expectReturn($upload, 'getPath', '/feeds');
        $this->expectReturn($this->feedMock, 'getUploadCollection', [$upload]);
        $this->expectReturn($this->fileDriverMock, 'isExists', true);

        $uploader = $this->createMock('MageOS\ShoppingFeed\Model\Uploader\UploaderInterface');
        $uploader->expects($this->once())->method('upload')->with('/tmp/feed.txt.gz')->willReturn(true);
        $uploaderFactory = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Uploader\UploaderFactory',
            ['create']
        );
        $uploaderFactory->expects($this->once())->method('create')->with($upload)->willReturn($uploader);

        $compressor = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Uploader\GzipCompressor',
            ['compress', 'remove']
        );
        $compressor->expects($this->once())->method('compress')->with('/tmp/feed.txt')->willReturn('/tmp/feed.txt.gz');
        $compressor->expects($this->once())->method('remove')->with('/tmp/feed.txt.gz');

        $eventManager = $this->createMock('Magento\Framework\Event\ManagerInterface');
        $eventManager->expects($this->once())->method('dispatch')->with(
            'mageos_shopping_feed_upload_after',
            $this->callback(static function (array $data) use ($upload): bool {
                return $data['upload'] === $upload && $data['result'] === true;
            })
        );

        $this->objectData['queue'] = null;
        $this->objectData['uploaderFactory'] = $uploaderFactory;
        $this->objectData['gzipCompressor'] = $compressor;
        $this->objectData['eventManager'] = $eventManager;
        $this->createModel();
        $this->model->setData('feed_file', '/tmp/feed.txt');

        $method = new \ReflectionMethod($this->model, 'processUploads');
        $this->assertSame($this->model, $method->invoke($this->model));
    }

    public function testRunException()
    {
        $this->setTestRunMocks();

        $this->expectReturn($this->batchMock, 'isEnabled', false);

        $this->createModel();
        $this->setExpectedException(
            'MageOS\ShoppingFeed\Model\Exception',
            'Generator must be created using existing feed - no Feed Id found!'
        );

        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Generator', $this->model->run());
    }

    protected function setTestRunMocks()
    {
        $this->expectReturn($this->dateTimeMock, 'getTimestamp', 1000000);
        $this->expectReturn($this->feedMock, 'getType', 'generic');
        $this->expectSelf(
            $this->productCollectionProviderMock,
            ['getCollection', 'setStoreId', 'addStoreFilter', 'getSelect']
        );

        $this->expectReturn($this->feedMock, 'getColumnsMap', []);
        $this->feedMock->expects($this->any())
            ->method('getConfig')
            ->will($this->returnCallback(function ($key, $default = '') {
                switch ($key) {
                    case 'filters_product_types':
                        return ['simple','configurable','bundle','virtual','downloadable'];
                    case 'filters_add_out_of_stock':
                    case 'categories_include_all_products':
                        return true;
                    case 'filters_attribute_sets':
                    case 'categories_provider_taxonomy_by_category':
                        return [];
                    default:
                        break;
                }
                return $default;
            }));
        $this->feedMock->expects($this->any())
            ->method('getFile')
            ->will($this->returnCallback(function ($key) {
                switch ($key) {
                    case 'feed':
                        return 'feed_%s.txt';
                    default:
                        return 'log_%s.txt';
                }
            }));

        $this->expectSelf($this->iteratorMock, 'walk');

        $this->objectData['queue'] = null;
    }

    public function testDesctruct()
    {
        $this->expectReturn($this->queueMock, 'getBatch', $this->batchMock);
        $this->createModel();

        $this->model = null;
        $this->assertEmpty($this->model);
    }
}
