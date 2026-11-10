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


namespace MageOS\ShoppingFeed\Test\Unit\Model\Product\Mapper\Generic\Simple;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use MageOS\ShoppingFeed\Test\Unit\Model\ModelFramework;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class ShippingTest
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ShippingTest extends ModelFramework
{
    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Shipping
     */
    protected $model;

    /**
     * @var array|string|null
     */
    protected $shippingCountryConfig = ['USA'];

    /** @var string */
    private string $outputFormat = 'delimited';

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->expectReturn($this->adapterMock, 'getFeed', $this->feedMock);
        $this->expectReturn($this->adapterMock, 'getProduct', $this->productMock);
        $this->expectReturn($this->productMock, 'getId', 1);
        $this->expectAdvencedReturn(
            $this->feedMock,
            'getConfig',
            $this->returnCallback(function ($arg) {
                switch ($arg) {
                    case 'output_params_format':
                        return $this->outputFormat;
                    case 'shipping_country':
                        return $this->shippingCountryConfig;
                    default:
                        return '';
                }
            })
        );

        $this->expectSelf($this->shippingProviderMock, 'prepareCache');

        // Set up null cache in shipping Mock, used in ;
        //$this->expectReturn($this->cacheMock, 'getCache', null);

        $shippingMock = $this->getModelMock(
            'MageOS\ShoppingFeed\Model\Product\Shipping',
            ['setRequest', 'collectRates', 'getFormatedValue', 'getProductWeight']
        );

        $this->expectReturn($shippingMock, 'getFormatedValue', 'shipping value');
        $this->expectReturn(
            $shippingMock,
            'setRequest',
            $this->getModelMock('Magento\Quote\Model\Quote\Address\RateRequest')
        );
        $this->expectReturn($shippingMock, 'getProductWeight', 1);
        $this->expectReturn($this->shippingProviderMock, 'getShipping', $shippingMock);

        $this->model = $this->objectManagerHelper->getObject(
            'MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Shipping',
            [
                'cache' => $this->cacheMock,
                'shippingProvider' => $this->shippingProviderMock,
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
        $this->model->addAdapter($this->adapterMock);
    }

    public function testMap()
    {
        $this->expectReturn($this->cacheMock, 'getCache', false);
        $this->expectReturn($this->shippingProviderMock, 'getCache', false);

        $this->assertEquals('shipping value', $this->model->map());
    }

    public function testXmlCachePreservesPunctuationAndDelimitedSwitchRecomputes(): void
    {
        $this->outputFormat = 'xml';
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $this->cacheMock);
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $this->shippingProviderMock);
        $this->expectReturn($this->cacheMock, 'getCache', false);
        $values = [['country' => 'US', 'region' => '', 'service' => 'Ground: 3-5 days, tracked', 'price' => '0 USD']];
        $text = 'US::Ground: 3-5 days, tracked:0 USD';
        $stored = 'xml-shipping-v1:' . json_encode(['values' => $values, 'text' => $text]);
        $this->expectReturn($this->shippingProviderMock, 'getCache', $stored);
        $mapped = $this->model->map();
        self::assertInstanceOf(\MageOS\ShoppingFeed\Model\Output\MappedValue::class, $mapped);
        self::assertSame($values, $mapped->values);
        self::assertSame($text, (string)$mapped);
        $this->outputFormat = 'delimited';
        self::assertSame('shipping value', $this->model->map());
    }

    public function testXmlRecomputesLegacyFlattenedCache(): void
    {
        $this->outputFormat = 'xml';
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $this->cacheMock);
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $this->shippingProviderMock);
        $this->expectReturn($this->cacheMock, 'getCache', false);
        $this->expectReturn($this->shippingProviderMock, 'getCache', 'US::ambiguous, value:5 USD');
        self::assertSame('shipping value', $this->model->map());
    }

    public function testMapInterfaceCache()
    {
        $this->expectReturn($this->cacheMock, 'getCache', false);
        $this->expectReturn($this->shippingProviderMock, 'getCache', 'cache value');

        $this->assertEquals('cache value', $this->model->map());
    }

    public function testMapCache()
    {
        $this->expectReturn($this->cacheMock, 'getCache', 'cache value');

        $this->assertEquals('cache value', $this->model->map());
    }

    /**
     * Non-array shipping_country values must skip shipping instead of reaching array_filter().
     *
     * @dataProvider nonArrayShippingCountryProvider
     */
    #[DataProvider('nonArrayShippingCountryProvider')]
    public function testMapNonArrayShippingCountry($shippingCountry)
    {
        $this->shippingCountryConfig = $shippingCountry;

        $this->assertEquals('', $this->model->map());
    }

    /**
     * @return array
     */
    public static function nonArrayShippingCountryProvider()
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'non-empty string' => ['USA'],
        ];
    }
}
