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


namespace MageOS\ShoppingFeed\Test\Unit\Model\Adapter\Type;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use MageOS\ShoppingFeed\Test\Unit\Model\ModelFramework;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class FeedTest
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class SimpleTest extends ModelFramework
{
    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple
     */
    protected $model;

    /**
     * @var array
     */
    protected $modelArguments = [];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->expectReturn($this->feedMock, 'getId', 1);
        $this->expectReturn($this->feedMock, 'getStore', $this->storeMock);
        $this->expectReturn($this->productMock, 'getId', 1);
        $this->expectReturn($this->productMock, 'getSku', 'S-K-U');
        $this->expectReturn($this->stockStateMock, 'getStockQty', 5);
        $this->expectSelf($this->storeMock, 'getCurrentCurrency');

        $filesystemMock = $this->getModelMock('Magento\Framework\Filesystem');
        $directoryMock = $this->getModelMock('\Magento\Framework\Filesystem\Directory\ReadInterface');
        $this->expectReturn($filesystemMock, 'getDirectoryRead', $directoryMock);

        $this->modelArguments = [
            'filesystem' => $filesystemMock,
            'feed' => $this->feedMock,
            'product' => $this->productMock,
            'feedTypesConfig' => $this->feedTypesConfigMock,
            'mapperFactory' => $this->mapperFactoryMock,
            'helper' => $this->helperMock,
            'weeeData' => $this->weeHelperMock,
            'taxData' => $this->taxHelperMock,
            'catalogHelper' => $this->catalogHelperMock,
            'catalogRuleCollectionFactory' => $this->catalogRuleCollectionFactoryMock,
            'productTypePrice' => $this->catalogProductPriceMock,
            'optionFactory' => $this->optionFactoryMock,
            'processFactory' => $this->processFactoryMock,
            'processCollectionFactory' => $this->processCollectionFactoryMock,
            'timezone' => $this->dateTimeMock,
            'stockState' => $this->stockStateMock,
            'date' => $this->dateMock,
            'cache' => $this->cacheMock,
            'filter' => $this->filterMock,
            'logger' => $this->loggerMock,
            'data' => ['generator' => $this->generatorMock]
        ];

        $this->model = $this->objectManagerHelper->getObject(
            'MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple',
            $this->modelArguments
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf('\MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple', $this->model);
    }

    public static function getTestMapValues()
    {
        return [['Return value', 'Return value'], ['', '']];
    }

    /**
     * @dataProvider getTestMapValues
     *
     * @param $value
     */
    #[DataProvider('getTestMapValues')]
    public function testMap($value, $expectedValue)
    {
        $this->expectReturn(
            $this->feedMock,
            'getColumnsMap',
            [
                'column' => [
                    'attribute' => 'some_attribute',
                    'column'    => 'column'
                ]
            ]
        );
        $this->expectAdvencedReturn(
            $this->feedMock,
            'getConfig',
            $this->returnCallback(
                function ($key, $default = '') {
                    switch ($key) {
                        case 'filters_skip_column_empty':
                            return ['column'];
                        case 'options_mode':
                            return 1;
                        default:
                            return $default;
                    }
                }
            )
        );

        $this->expectReturn($this->productMock, 'getCategoryIds', []);
        $this->expectAdvencedReturn(
            $this->feedTypesConfigMock,
            'isAllowedDirective',
            $this->onConsecutiveCalls(true, true, true, false)
        );
        $this->expectReturn($this->feedTypesConfigMock, 'getDirective', ['mappers' => ['default' => []]]);
        $this->expectReturn($this->mapperMock, 'map', $value);
        $this->expectAdvencedReturn($this->mapperMock, 'format', $this->returnArgument(0));
        $this->expectReturn($this->mapperFactoryMock, 'create', $this->mapperMock);
        $this->expectReturn($this->mapperFactoryMock, 'getMapperData', ['format' => true]);
        $this->expectReturn($this->cacheMock, 'getCache', false);

        $rows = $this->model->map();
        $expected = empty($expectedValue) ? [] : [0 => ['column' => $expectedValue]];
        $this->assertEquals($expected, $rows);
    }

    /**
     * @dataProvider getTestMapValues
     *
     * @param $value
     */
    #[DataProvider('getTestMapValues')]
    public function testMapRowFormat($value, $expectedValue)
    {
        $this->expectReturn(
            $this->feedMock,
            'getColumnsMap',
            [
                'column' => [
                    'attribute' => 'some_attribute',
                    'column'    => 'column'
                ]
            ]
        );

        $this->expectAdvencedReturn(
            $this->feedMock,
            'getConfig',
            $this->returnCallback(
                function ($key, $default = '') {
                    switch ($key) {
                        case 'filters_skip_column_empty':
                            return ['column'];
                        case 'options_mode':
                            return 1;
                        default:
                            return $default;
                    }
                }
            )
        );

        $this->expectReturn($this->productMock, 'getCategoryIds', []);
        $this->expectAdvencedReturn(
            $this->feedTypesConfigMock,
            'isAllowedDirective',
            $this->onConsecutiveCalls(true, false)
        );
        $this->expectReturn($this->feedTypesConfigMock, 'getDirective', ['mappers' => ['default' => []]]);
        $this->expectReturn($this->mapperMock, 'map', $value);
        $this->expectReturn($this->mapperFactoryMock, 'create', $this->mapperMock);
        $this->expectReturn($this->mapperFactoryMock, 'getMapperData', ['format' => true]);
        $this->expectReturn($this->cacheMock, 'getCache', false);

        $rows = $this->model->map();

        $expected = empty($expectedValue) ? [] : [0 => ['column' => $expectedValue]];
        $this->assertEquals($expected, $rows);
    }

    public function testHasSpecialPriceMsrp()
    {
        $this->expectReturn($this->helperMock, 'hasMsrp', true);

        $this->assertEquals(true, $this->model->hasSpecialPrice());
    }

    public function testMapSameColumnName()
    {
        $this->expectReturn($this->feedTypesConfigMock, 'isAllowedDirective', true);
        $this->expectAdvencedReturn(
            $this->feedMock,
            'getConfig',
            $this->returnCallback(
                function ($key, $default = '') {
                    switch ($key) {
                        case 'filters_skip_column_empty':
                            return ['column'];
                        default:
                            return $default;
                    }
                }
            )
        );

        $this->expectReturn(
            $this->feedMock,
            'getColumnsMap',
            [
                'column' => [
                    'attribute' => 'some_attribute',
                    'column'    => 'column'
                ],
                'column2' => [
                    'attribute' => 'some_attribute',
                    'column'    => 'column'
                ],
                'column3' => [
                    'attribute' => 'some_attribute',
                    'column'    => 'column'
                ]
            ]
        );

        $this->expectReturn($this->cacheMock, 'getCache', 'value');

        $expected = [
                ['column' => ['value', 'value', 'value']]
        ];

        $this->assertEquals($expected, $rows = $this->model->map());
    }

    public function testRowsWithDifferentParametersDoNotShareACachedValue(): void
    {
        // A real cache: a mock cannot show one key reading another key's subtree.
        $arguments = ['cache' => new \MageOS\ShoppingFeed\Model\Generator\Cache()] + $this->modelArguments;
        $model = $this->objectManagerHelper->getObject(
            'MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple',
            $arguments
        );
        self::assertInstanceOf(\MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple::class, $model);
        $feedTypes = $this->feedTypesConfigMock;
        $mappers = $this->mapperFactoryMock;
        $feed = $this->feedMock;
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $feedTypes);
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $mappers);
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $feed);
        $feedTypes->method('isAllowedDirective')->willReturn(true);
        $feedTypes->method('getDirective')->willReturn([]);
        $calls = 0;
        $mapper = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Mapper\MapperAbstract::class);
        $mapper->method('map')->willReturnCallback(function (array $column) use (&$calls) {
            $calls++;
            return array_key_exists('param', $column) ? 'param:' . json_encode($column['param']) : 'no param';
        });
        $mappers->method('create')->willReturn($mapper);
        $row = ['column' => 'tier', 'attribute' => 'directive_conditional_value'];

        $this->assertSame('param:"thumbnail"', $model->getMapValue($row + ['param' => 'thumbnail']));
        $this->assertSame('param:"image"', $model->getMapValue($row + ['param' => 'image']));
        // Previously a shorter key and returned the cache's internal array of parameter results.
        $this->assertSame('no param', $model->getMapValue($row));
        $this->assertSame('param:null', $model->getMapValue($row + ['param' => null]));
        $this->assertSame('param:"thumbnail"', $model->getMapValue($row + ['param' => 'thumbnail']));
        $this->assertSame(4, $calls, 'a repeated mapping is served from the cache');
        // A column name containing the cache path separator gets its own entry too.
        $this->assertSame('no param', $model->getMapValue(['column' => 'a/b'] + $row));
        $this->assertSame(5, $calls);
        // Malformed UTF-8 in two different parameters must not collapse to one entry.
        $model->getMapValue($row + ['param' => "\xB1"]);
        $model->getMapValue($row + ['param' => "\xB2"]);
        $this->assertSame(7, $calls);
    }

    public function testReplaceEmptyValuesKeepsAZeroFromAnEarlierRow(): void
    {
        $arguments = ['cache' => new \MageOS\ShoppingFeed\Model\Generator\Cache()] + $this->modelArguments;
        $model = $this->objectManagerHelper->getObject(
            'MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple',
            $arguments
        );
        self::assertInstanceOf(\MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple::class, $model);
        $feedTypes = $this->feedTypesConfigMock;
        $mappers = $this->mapperFactoryMock;
        $feed = $this->feedMock;
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $feedTypes);
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $mappers);
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $feed);
        $feedTypes->method('isAllowedDirective')->willReturn(true);
        $feedTypes->method('getDirective')->willReturn([]);
        $mapper = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Mapper\MapperAbstract::class);
        $mapper->method('map')->willReturn('');
        $mappers->method('create')->willReturn($mapper);
        $feed->method('getConfig')->willReturnCallback(
            static fn (string $path, $default = null) => $path === 'filters_map_replace_empty_columns' ? [
                ['column' => 'shipping_fee', 'static' => '0', 'order' => 1],
                ['column' => 'shipping_fee', 'static' => '4.99', 'order' => 2],
            ] : $default
        );

        $this->assertSame(
            '0',
            $model->getMapValue(['column' => 'shipping_fee', 'attribute' => 'directive_static_value'])
        );
    }

    public function testHasSpecialPriceByCatalogRulesTrue()
    {
        $this->expectReturn($this->helperMock, 'hasMsrp', true);
        $this->expectReturn($this->feedMock, 'getConfig', true);
        $this->expectReturn($this->productMock, 'getPrice', 20);
        $this->expectReturn($this->productMock, 'getSpecialPrice', 10);
        $this->expectReturn($this->productMock, 'hasSpecialPrice', true);

        $this->catalogProductPriceMock->expects($this->any())
            ->method('calculatePrice')
            ->will($this->returnValue(5));

        $this->assertEquals(true, $this->model->hasSpecialPrice());
    }

    public function testHasSpecialPriceByCatalogRulesFalse()
    {
        $this->expectReturn($this->helperMock, 'hasMsrp', true);
        $this->expectReturn($this->feedMock, 'getConfig', true);
        $this->expectReturn($this->productMock, 'getPrice', 20);
        $this->expectReturn($this->productMock, 'getSpecialPrice', 3);
        $this->expectReturn($this->productMock, 'hasSpecialPrice', true);
        $this->expectReturn($this->catalogProductPriceMock, 'calculatePrice', 5);

        $this->assertEquals(true, $this->model->hasSpecialPrice());
    }

    public function testHasSpecialPriceDate()
    {
        $this->expectReturn($this->productMock, 'getPrice', 20);
        $this->expectReturn($this->productMock, 'getSpecialPrice', 10);
        $this->expectReturn($this->productMock, 'getSpecialFromDate', '2016-03-01 00:00:00');
        $this->expectReturn($this->productMock, 'getSpecialToDate', '2016-03-10 00:00:00');
        $this->expectAdvencedReturn(
            $this->dateTimeMock,
            'date',
            $this->onConsecutiveCalls(
                new \DateTime('2016-03-01'),
                new \DateTime('2016-03-05'),
            )
        );
        $this->expectAdvencedReturn(
            $this->dateMock,
            'isEmptyDate',
            $this->onConsecutiveCalls(false, false)
        );

        $this->assertEquals(true, $this->model->hasSpecialPrice(false));
    }

    public function testGetSalePriceEffectiveDates()
    {
        $this->expectReturn($this->helperMock, 'hasMsrp', true);

        $this->assertEquals(false, $this->model->getSalePriceEffectiveDates());
    }

    public function testGetSalePriceEffectiveDatesFalse()
    {
        $this->expectReturn($this->productMock, 'getSpecialPrice', 0);

        $this->assertEquals(false, $this->model->getSalePriceEffectiveDates());
    }

    public function testGetSalePriceEffectiveDatesCatalogRules()
    {
        $timezone = new \DateTimeZone('America/Chicago');

        $this->expectReturn($this->helperMock, 'hasMsrp', true);
        $this->expectReturn($this->feedMock, 'getConfig', true);
        $this->expectReturn($this->productMock, 'getPrice', 20);
        $this->expectReturn($this->productMock, 'getSpecialPrice', 3);
        $this->expectReturn($this->productMock, 'hasSpecialPrice', true);
        $this->expectReturn($this->catalogProductPriceMock, 'calculatePrice', 2);
        $this->expectReturn($this->dateTimeMock, 'getConfigTimezone', $timezone->getName());

        $dateMock = new \DateTime('2016-01-01', $timezone);
        $dateMock->setTime(1, 0);

        $this->expectReturn($this->catalogRuleMock, 'getData', $dateMock->format('Y-m-d'));

        $expected = ['start' => $dateMock, 'end' => $dateMock];
        $this->assertEquals($expected, $this->model->getSalePriceEffectiveDates());
    }

    public function testGetSalePriceEffectiveDatesCatalogRulesWithoutDateSet()
    {
        $this->expectReturn($this->helperMock, 'hasMsrp', true);
        $this->expectReturn($this->feedMock, 'getConfig', true);
        $this->expectReturn($this->productMock, 'getPrice', 20);
        $this->expectReturn($this->productMock, 'getSpecialPrice', 3);
        $this->expectReturn($this->productMock, 'hasSpecialPrice', true);
        $this->expectReturn($this->catalogProductPriceMock, 'calculatePrice', 2);

        $dateMock = new \DateTime('@1451649600');

        $this->expectReturn($this->catalogRuleMock, 'getData', false);
        $this->expectReturn($this->dateTimeMock, 'date', $dateMock);

        $endDateMock = clone $dateMock;
        $expected = ['start' => $dateMock, 'end' => $endDateMock->add(new \DateInterval('P1Y'))];
        $result = $this->model->getSalePriceEffectiveDates();
        $this->assertEquals($expected, $result);
    }

    public function testGetInventoryCount()
    {
        $this->assertEquals(5, $this->model->getInventoryCount());
    }

    public function testMapEmptyValuesLoop()
    {
        $args = ['column' => 'columnName'];
        $this->expectReturn(
            $this->feedMock,
            'getColumnsMap',
            [
                'columnName' => ['empty_replaced' => true]
            ]
        );

        $this->assertEquals('', $this->model->mapEmptyValues($args));
    }

    public function testMapEmptyValues()
    {
        $args = ['column' => 'columnName'];
        $this->expectReturn(
            $this->feedMock,
            'getColumnsMap',
            ['columnName' => []]
        );
        $this->expectAdvencedReturn(
            $this->feedMock,
            'getConfig',
            $this->returnCallback(
                function ($key, $default = '') {
                    switch ($key) {
                        case 'filters_skip_column_empty':
                            return ['column'];
                        case 'filters_map_replace_empty_columns':
                            return [[
                            'column' => 'columnName',
                            'static' => 'static value',
                            'attribute' => 'directive_static_value'
                        ]];
                        default:
                            return $default;
                    }
                }
            )
        );

        $this->assertEquals('static value', $this->model->mapEmptyValues($args));
    }

    public function testMapEmptyValuesGetMapValue()
    {
        $args = ['column' => 'columnName'];
        $this->expectReturn(
            $this->feedMock,
            'getColumnsMap',
            ['columnName' => []]
        );
        $this->expectAdvencedReturn(
            $this->feedMock,
            'getConfig',
            $this->returnCallback(
                function ($key, $default = '') {
                    switch ($key) {
                        case 'filters_map_replace_empty_columns':
                            return [[
                            'column' => 'columnName'
                        ]];
                        default:
                            return $default;
                    }
                }
            )
        );
        $this->expectReturn($this->cacheMock, 'getCache', 'map value');
        $this->assertEquals('map value', $this->model->mapEmptyValues($args));
    }

    public function testGetters()
    {
        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Feed', $this->model->getFeed());
        $this->assertInstanceOf('Magento\Framework\Stdlib\DateTime\Timezone', $this->model->getTimezone());
        $this->assertInstanceOf('Magento\Catalog\Model\Product', $this->model->getProduct());
    }

    /**
     * @dataProvider getPricesProvider
     *
     * @param $quantity
     * @param $algorithm
     * @param $expected
     */
    #[DataProvider('getPricesProvider')]
    public function testGetPrices($quantity, $algorithm, $expected)
    {
        $configMock = $this->getModelMock('\Magento\Framework\DataObject', ['getAlgorithm']);
        $this->expectReturn($configMock, 'getAlgorithm', $algorithm);
        $this->expectReturn($this->taxHelperMock, 'getConfig', $configMock);
        $this->expectReturn($this->helperMock, 'getQuantityIcrements', $quantity);
        $this->expectReturn($this->weeHelperMock, 'getAmountExclTax', 0);
        $this->expectReturn($this->weeHelperMock, 'isTaxable', true);
        $this->expectReturn($this->weeHelperMock, 'getProductWeeeAttributesForRenderer', []);
        $this->expectAdvencedReturn(
            $this->catalogHelperMock,
            'getTaxPrice',
            $this->returnCallback(
                function ($product, $price, $tax = false) {
                    return $tax ? $price * 1.2 : $price * 1.0;
                }
            )
        );

        $this->expectReturn($this->productMock, 'getPrice', 200);
        $this->expectReturn($this->productMock, 'getSpecialPrice', 150);
        $this->expectReturn($this->productMock, 'getFinalPrice', 100);
        $this->expectSelf($this->storeMock, 'getBaseCurrency');
        $this->expectAdvencedReturn($this->storeMock, 'convert', $this->returnArgument(0));
        $this->expectReturn($this->catalogProductPriceMock, 'calculatePrice', 150);

        // Testing the method
        $prices = $this->model->getPrices();
        $this->assertEquals($expected, $prices);

        // Testing the cache
        $prices = $this->model->getPrices();
        $this->assertEquals($expected, $prices);
    }

    public static function getPricesProvider()
    {
        return [
            [1, '', [
                'p_excl_tax' => 200.0,
                'p_incl_tax' => 240.0,
                'sp_excl_tax' => 100.0,
                'sp_incl_tax' => 120.0
            ]],
            [10, '', [
                'p_excl_tax' => 2000.0,
                'p_incl_tax' => 2400.0,
                'sp_excl_tax' => 1000.0,
                'sp_incl_tax' => 1200.0
            ]],
            [10, 'UNIT_BASE_CALCULATION', [
                'p_excl_tax' => 2000.0,
                'p_incl_tax' => 2400.0,
                'sp_excl_tax' => 1000.0,
                'sp_incl_tax' => 1200.0
            ]],
        ];
    }

    public function testConstructProductException()
    {
        $productMock = $this->getModelMock('Magento\Catalog\Model\Product', ['getId']);
        $this->expectReturn($productMock, 'getId', false);
        $this->modelArguments['product'] = $productMock;

        $this->setExpectedException(
            '\MageOS\ShoppingFeed\Model\Exception',
            "Adapter can't be created, product is not loaded"
        );

        $this->model = $this->objectManagerHelper->getObject(
            'MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple',
            $this->modelArguments
        );
    }

    public function testGetMapValueException()
    {
        $attribute = false;

        $resource = new \Magento\Framework\DataObject();
        $resource->setData('attribute', ['some_attribute' => $attribute]);
        $this->expectReturn($this->productMock, 'getResource', $resource);
        $this->expectReturn($this->cacheMock, 'getCache', false);

        $this->setExpectedException(
            '\MageOS\ShoppingFeed\Model\Exception',
            "Couldn't find attribute 'some_attribute'."
        );

        $this->model->getMapValue(
            [
            'attribute' => 'some_attribute',
            'column'    => 'column'
            ]
        );
    }

    public function testGetMapValueWithDirective()
    {
        $this->expectReturn($this->feedTypesConfigMock, 'isAllowedDirective', true);
        $this->expectReturn($this->feedTypesConfigMock, 'getDirective', 'directive');
        $this->expectReturn($this->mapperMock, 'map', 'Return value');
        $this->expectReturn($this->mapperFactoryMock, 'create', $this->mapperMock);
        $this->expectReturn($this->cacheMock, 'getCache', false);

        /**
 * Testing the method
*/
        $value = $this->model->getMapValue(
            [
            'attribute' => 'some_attribute',
            'column'    => 'column'
            ]
        );

        $this->assertEquals('Return value', $value);

        /**
 * Testing the cache
*/
        $value = $this->model->getMapValue(
            [
            'attribute' => 'some_attribute',
            'column'    => 'column'
            ]
        );

        $this->assertEquals('Return value', $value);
    }

    /**
     * @dataProvider getMapValueProvider
     */
    #[DataProvider('getMapValueProvider')]
    public function testGetMapValueWithoutDirective($attributeData = [])
    {
        $this->expectAdvencedReturn($this->feedMock, 'getConfig', $this->returnArgument(1));

        $attribute = $this->getModelMock(
            'Magento\Eav\Model\Entity\Attribute',
            ['getSourceModel', 'getAttributeCode', 'getFrontendInput', 'getSource']
        );
        $this->expectReturn($attribute, 'getSourceModel', $attributeData['source_model']);
        $this->expectReturn($attribute, 'getFrontendInput', $attributeData['frontend_input']);
        $this->expectReturn($attribute, 'getAttributeCode', $attributeData['code']);

        if (isset($attributeData['options'])) {
            $options = [];
            foreach ($attributeData['options'] as $option) {
                $options[] = ['label' => ucfirst($option), 'value' => $option];
            }
            $source = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Source\SourceInterface::class);
            $source->method('getAllOptions')->willReturn($options);
            $this->expectReturn($attribute, 'getSource', $source);
        }

        $this->expectReturn($this->productMock, 'getData', $attributeData['data']);
        $resource = new \Magento\Framework\DataObject();
        $resource->setData('attribute', ['some_attribute' => $attribute]);

        $this->expectReturn($this->productMock, 'getResource', $resource);
        $this->expectReturn($this->cacheMock, 'getCache', false);

        /**
 * Testing the method
*/
        $value = $this->model->getMapValue(
            [
            'attribute' => 'some_attribute',
            'column'    => 'column'
            ]
        );

        $this->assertEquals($attributeData['expected'], $value);

        /**
 * Testing the cache
*/
        $value = $this->model->getMapValue(
            [
            'attribute' => 'some_attribute',
            'column'    => 'column'
            ]
        );

        $this->assertEquals($attributeData['expected'], $value);
    }

    public static function getMapValueProvider()
    {
        return [
            [['code' => 'attribute_code_boolean', 'source_model' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'data' => true, 'expected' => 'Yes', 'frontend_input' => '']],
            [['code' => 'attribute_code_value', 'source_model' => '',
                'data' => 'Data', 'expected' => 'Data', 'frontend_input' => '']],
            [['code' => 'attribute_code_select1', 'source_model' => '',
                'data' => ['option1', 'option2'], 'expected' => 'Option1, Option2', 'frontend_input' => 'select',
                'options' => ['option1', 'option2']
            ]],
            [['code' => 'attribute_code_select2', 'source_model' => '',
                'data' => ['option1'], 'expected' => 'Option1', 'frontend_input' => 'select',
                'options' => ['option1', 'option2']
            ]],
            [['code' => 'attribute_code_select3', 'source_model' => '',
                'data' => '', 'expected' => '', 'frontend_input' => 'select',
                'options' => ['option1', 'option2']
            ]]
        ];
    }

    public function testGetChildrenCount()
    {
        $this->assertEquals(1, $this->model->getChildrenCount());
    }

    public function testGetStoreException()
    {
        $this->setExpectedException('\MageOS\ShoppingFeed\Model\Exception', 'Adapter failed, feed was not set!');

        $this->feedMock = $this->getModelMock('MageOS\ShoppingFeed\Model\Feed', ['getId', 'getStore']);
        $this->modelArguments['feed'] = $this->feedMock;
        $this->model = $this->objectManagerHelper->getObject(
            'MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple',
            $this->modelArguments
        );
    }

    public function testIsDuplicate()
    {
        $this->expectReturn($this->adapterMock, 'getProduct', $this->productMock);
        $this->model->setParentAdapter($this->adapterMock);
        $this->expectReturn($this->processMock, 'getId', 1);
        $this->expectReturn($this->processMock, 'getStatus', 1);

        $this->assertEquals(true, $this->model->isDuplicate());
    }

    public function testIsDuplicateTestMode()
    {
        $this->model->setTestMode();
        $this->assertEquals(false, $this->model->isDuplicate());
    }

    public function testIsSkippedTrue()
    {
        $this->expectReturn($this->productMock, 'getData', 1);

        $this->assertEquals(true, $this->model->isSkipped());
    }

    public function testIsSkippedFalse()
    {
        $this->assertEquals(false, $this->model->isSkipped());
    }
}
