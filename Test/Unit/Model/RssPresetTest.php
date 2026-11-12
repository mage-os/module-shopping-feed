<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model;

use MageOS\ShoppingFeed\Model\FeedTypes\Config\Converter;
use MageOS\ShoppingFeed\Model\Output\Rss;
use Magento\Framework\Json\Decoder;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class RssPresetTest extends TestCase
{
    /** @return array<string,mixed> */
    public static function presets(): array
    {
        $document = new \DOMDocument();
        $document->load(dirname(__DIR__, 3) . '/etc/mageos_shopping_feed.xml');
        return (new Converter(new Decoder(new Json())))->convert($document)['feed'];
    }

    public function testEveryDestinationHasUsableCanonicalXmlDefaults(): void
    {
        $count = 0;
        foreach (self::presets() as $type => $feed) {
            $config = $feed['default_feed_config']['output_params'];
            if (in_array($type, ['generic', 'openai_google_compatible'], true)) {
                self::assertArrayNotHasKey('xml_preset', $config);
                continue;
            }
            $count++;
            $writer = new Rss($config, 'Store & Café', 'https://example.com/?a=1&b=2');
            $id = $type === 'tiktok_catalog' ? 'sku_id' : 'id';
            $row = [$id => 'SKU-1', 'title' => '<Shirt> & café', 'empty' => '', 'quantity' => 0];
            if ($type === 'google_local_inventory') {
                $row['store_code'] = 'STORE-1';
            }
            $document = new \DOMDocument();
            self::assertTrue($document->loadXML($writer->header() . $writer->row($row) . $writer->footer()));
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('g', 'http://base.google.com/ns/1.0');
            self::assertSame('SKU-1', $xpath->evaluate('string(/rss/channel/item/g:id)'), $type);
            $title = $type === 'pinterest_catalog' ? 'title' : 'g:title';
            self::assertSame('<Shirt> & café', $xpath->evaluate('string(/rss/channel/item/' . $title . ')'));
            self::assertSame('0', $xpath->evaluate('string(/rss/channel/item/g:quantity)'));
            self::assertSame(0.0, $xpath->evaluate('count(/rss/channel/item/g:empty)'));
            self::assertSame('Store & Café', $xpath->evaluate('string(/rss/channel/title)'));
            self::assertSame('https://example.com/?a=1&b=2', $xpath->evaluate('string(/rss/channel/link)'));
            self::assertTrue($document->loadXML($writer->header() . $writer->footer()));
        }
        self::assertSame(6, $count);
    }

    public function testProviderSampleNamespaces(): void
    {
        foreach (['meta_catalog' => ['additional_image_link'],
            'pinterest_catalog' => ['title', 'description', 'link', 'sale_price']] as $type => $plain) {
            $preset = self::presets()[$type]['default_feed_config']['output_params'];
            $writer = new Rss($preset, 'Store', 'https://example.com');
            $document = new \DOMDocument();
            $fields = array_fill_keys($plain, 'value');
            $fields['id'] = 'SKU-1';
            $writer->validateColumns(array_keys($fields));
            self::assertTrue($document->loadXML($writer->header() . $writer->row($fields) . $writer->footer()));
            foreach ($plain as $name) {
                $node = $document->getElementsByTagName($name)->item(0);
                self::assertNotNull($node);
                self::assertNull($node->namespaceURI, $type . ':' . $name);
            }
            self::assertSame(1, $document->getElementsByTagNameNS('http://base.google.com/ns/1.0', 'id')->length);
        }
    }

    public function testShippingTaxAndImagesAreStructuredAndRepeated(): void
    {
        $config = self::presets()['google_shopping']['default_feed_config']['output_params'];
        $writer = new Rss($config, 'Store', 'https://example.com');
        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($writer->header() . $writer->row([
            'shipping' => 'US::Ground:0 USD,US:CA:Express:5 USD',
            'tax' => 'US:CA:7.25:y', 'additional_image_link' => 'https://example.com/a,https://example.com/b'
        ]) . $writer->footer()));
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('g', 'http://base.google.com/ns/1.0');
        self::assertSame(2.0, $xpath->evaluate('count(//g:shipping)'));
        self::assertSame('0 USD', $xpath->evaluate('string(//g:shipping[1]/g:price)'));
        self::assertSame(0.0, $xpath->evaluate('count(//g:shipping[1]/g:region)'));
        self::assertSame('CA', $xpath->evaluate('string(//g:shipping[2]/g:region)'));
        self::assertSame('7.25', $xpath->evaluate('string(//g:tax/g:rate)'));
        self::assertSame(2.0, $xpath->evaluate('count(//g:additional_image_link)'));
    }

    public function testStructuredValuesPreservePunctuation(): void
    {
        $config = self::presets()['google_shopping']['default_feed_config']['output_params'];
        $writer = new Rss($config, 'Store', 'https://example.com');
        $shipping = new \MageOS\ShoppingFeed\Model\Output\MappedValue([
            ['country' => 'US', 'region' => '', 'service' => 'Ground: 3-5 days, tracked', 'price' => '0 USD']
        ], 'US::Ground: 3-5 days, tracked:0 USD');
        $images = new \MageOS\ShoppingFeed\Model\Output\MappedValue([
            'https://example.com/w_100,h_200/a.jpg', 'https://example.com/b.jpg'
        ], 'https://example.com/w_100,h_200/a.jpg,https://example.com/b.jpg');
        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($writer->header() . $writer->row([
            'shipping' => [$shipping, $shipping], 'additional_image_link' => $images
        ]) . $writer->footer()));
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('g', 'http://base.google.com/ns/1.0');
        self::assertSame(2.0, $xpath->evaluate('count(//g:shipping)'));
        self::assertSame('Ground: 3-5 days, tracked', $xpath->evaluate('string(//g:shipping/g:service)'));
        self::assertSame(2.0, $xpath->evaluate('count(//g:additional_image_link)'));
        self::assertSame(
            'https://example.com/w_100,h_200/a.jpg',
            $xpath->evaluate('string(//g:additional_image_link[1])')
        );
    }

    public function testLocalInventoryPickupCostUsesNestedFields(): void
    {
        $config = self::presets()['google_local_inventory']['default_feed_config']['output_params'];
        $writer = new Rss($config, 'Store', 'https://example.com');
        $xml = $writer->row(['pickup_method' => 'buy', 'pickup_sla' => 'same_day', 'pickup_cost' => '3 EUR:20 EUR']);
        self::assertStringContainsString('<g:pickup_cost_flat_rate>3 EUR</g:pickup_cost_flat_rate>', $xml);
        self::assertStringContainsString('<g:pickup_cost_free_threshold>20 EUR</g:pickup_cost_free_threshold>', $xml);
        self::assertStringContainsString('<g:pickup_sla>same_day</g:pickup_sla>', $xml);
        $xml = $writer->row(['pickup_cost' => '0 EUR:']);
        self::assertStringContainsString('<g:pickup_cost_flat_rate>0 EUR</g:pickup_cost_flat_rate>', $xml);
        self::assertStringNotContainsString('pickup_cost_free_threshold', $xml);
    }

    public function testMalformedCompoundStopsGeneration(): void
    {
        $config = self::presets()['google_shopping']['default_feed_config']['output_params'];
        $writer = new Rss($config, 'Store', 'https://example.com');
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $writer->row(['shipping' => 'US:5 USD']);
    }

    public function testGeneratorUsesCanonicalPresetDespiteSavedGenericWrappers(): void
    {
        $config = $this->createMock(\MageOS\ShoppingFeed\Model\FeedTypes\Config::class);
        $config->method('getFeed')->willReturn(self::presets()['google_local_inventory']);
        $feed = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Feed::class)->disableOriginalConstructor()
            ->onlyMethods(['getColumnsMap', 'getStore'])->getMock();
        (new \ReflectionProperty($feed, 'feedTypesConfig'))->setValue($feed, $config);
        $feed->setData(['type' => 'google_local_inventory', 'name' => 'Store & Co',
            'config' => new \Magento\Framework\DataObject([
                'output_params_format' => 'xml', 'output_params_xml_header' => '<wrong>',
                'output_params_xml_item' => 'wrong', 'output_params_xml_footer' => '</wrong>'
            ])]);
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->method('getBaseUrl')->willReturn('https://example.com');
        $feed->method('getStore')->willReturn($store);
        $feed->method('getColumnsMap')->willReturn([['column' => 'id'], ['column' => 'store_code']]);
        $generator = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Generator::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $driver = $this->createMock(\Magento\Framework\Filesystem\Driver\File::class);
        $writes = [];
        $driver->method('fileWrite')->willReturnCallback(function ($handle, $value) use (&$writes) {
            $writes[] = $value;
            return strlen($value);
        });
        (new \ReflectionProperty($generator, 'feed'))->setValue($generator, $feed);
        (new \ReflectionProperty($generator, 'fileDriver'))->setValue($generator, $driver);
        $generator->setData('temporary_handle', 'handle');
        $method = new \ReflectionMethod($generator, 'writeFeed');
        $method->invoke($generator, [], false);
        $method->invoke($generator, ['id' => 'SKU-1', 'store_code' => 'STORE-1']);
        self::assertStringContainsString('<rss ', $writes[0]);
        self::assertStringNotContainsString('<wrong>', implode('', $writes));
        self::assertStringContainsString('<g:store_code>STORE-1</g:store_code>', $writes[1]);
        self::assertSame(1, $generator->getCountProductsExported());
    }

    public function testManualStructureFieldsAreOnlyShownForGenericFeeds(): void
    {
        $options = $this->createMock(\MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Options::class);
        $options->method('get')->willReturn([]);
        $fields = new \MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Fields($options);
        foreach (array_keys(self::presets()) as $type) {
            $feed = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Feed::class)
                ->disableOriginalConstructor()->onlyMethods([])->getMock();
            $feed->setData('type', $type);
            $general = $fields->get($feed)['general'];
            self::assertSame($type === 'generic', isset($general['output_params_xml_header']));
            self::assertSame($type === 'generic', isset($general['output_params_xml_item']));
            if ($type === 'openai_google_compatible') {
                self::assertSame(['delimited'], array_column($general['output_params_format']['options'], 'value'));
            }
        }
    }

    public function testSavedOpenAiXmlSelectionCannotGenerateAnUnsupportedFeed(): void
    {
        $feed = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Feed::class)->disableOriginalConstructor()
            ->onlyMethods(['getData'])->getMock();
        $feed->method('getData')->willReturn('openai_google_compatible');
        $generator = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Generator::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        (new \ReflectionProperty($generator, 'feed'))->setValue($generator, $feed);
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        (new \ReflectionMethod($generator, 'getXmlWriter'))->invoke($generator);
    }
}
