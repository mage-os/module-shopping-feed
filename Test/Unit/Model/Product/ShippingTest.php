<?php

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product;

use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Product\Shipping;
use MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ShippingTest extends TestCase
{
    public function testStructuredShippingRetainsServicePunctuationAndLegacyBytes(): void
    {
        $shipping = $this->getMockBuilder(Shipping::class)->disableOriginalConstructor()
            ->onlyMethods(['minimiseData', 'getPriceTax'])->getMock();
        $shipping->method('minimiseData')->willReturnArgument(0);
        $shipping->method('getPriceTax')->willReturnArgument(0);
        $adapter = $this->createMock(Simple::class);
        $adapter->method('getData')->willReturn('USD');
        (new \ReflectionProperty(Shipping::class, 'adapter'))->setValue($shipping, $adapter);
        (new \ReflectionProperty(Shipping::class, 'rates'))->setValue($shipping, [
            'US' => ['*' => [['title' => 'Ground: 3-5 days, tracked', 'methods' => [
                ['title' => 'Delivery', 'price' => 5]
            ]]]]
        ]);
        $legacy = 'US::Ground: 3-5 days, tracked - Delivery:5.00 USD';
        self::assertSame($legacy, $shipping->getFormatedValue());
        $value = $shipping->getFormatedValue(true);
        self::assertInstanceOf(\MageOS\ShoppingFeed\Model\Output\MappedValue::class, $value);
        self::assertSame($legacy, (string)$value);
        self::assertSame('Ground: 3-5 days, tracked - Delivery', $value->values[0]['service']);
        self::assertSame('5.00 USD', $value->values[0]['price']);
    }

    /** @dataProvider shippingMethods */
    #[DataProvider('shippingMethods')]
    public function testAllowedCarriersHandleUnconfiguredMethods($methods, array $expected): void
    {
        $feed = $this->createMock(Feed::class);
        $feed->method('getConfig')->willReturnCallback(
            static fn ($key) => $key === 'shipping_methods' ? $methods : ['ups']
        );
        $adapter = $this->createMock(Simple::class);
        $adapter->method('getFeed')->willReturn($feed);
        $shipping = (new \ReflectionClass(Shipping::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Shipping::class, 'adapter'))->setValue($shipping, $adapter);
        self::assertSame($expected, array_values($shipping->getAllowedCarriers()));
    }

    public static function shippingMethods(): array
    {
        return [
            [null, []], ['', []], ['flatrate_flatrate', []],
            [[[], 123, 'invalid', null], []],
            [['flatrate_flatrate', 'ups_03', 'flatrate_other', 'freeshipping_freeshipping'],
                ['flatrate', 'freeshipping']],
        ];
    }
}
