<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product;

use Magento\Framework\DataObject;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Generator\Cache;
use MageOS\ShoppingFeed\Model\Output\MappedValue;
use MageOS\ShoppingFeed\Model\Output\Rss;
use MageOS\ShoppingFeed\Model\Output\Xml;
use MageOS\ShoppingFeed\Model\Product\Filter;
use MageOS\ShoppingFeed\Model\Product\ModifierChain;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class XmlModifiersTest extends TestCase
{
    public function testXmlIgnoresInactiveDelimiterAndEncodingSettings(): void
    {
        $filter = $this->filter([
            ['column' => 'title', 'operation' => 'uppercase'],
            ['column' => 'markup', 'operation' => 'escape']
        ], ['output_params_delimiter' => ';', 'output_params_encoding' => 'ISO-8859-1']);
        $values = $filter->modifyRows([[
            'title' => $filter->cleanField('café; 東京', ['column' => 'title']),
            'markup' => $filter->cleanField('<b>A & B</b>', ['column' => 'markup'])
        ]])[0];
        $writer = new Xml('<items>', 'item', '</items>');
        $xml = simplexml_load_string($writer->header() . $writer->row($values) . $writer->footer());
        self::assertInstanceOf(\SimpleXMLElement::class, $xml);
        self::assertSame('CAFÉ; 東京', (string)$xml->item->title);
        self::assertSame('&lt;b&gt;A &amp; B&lt;/b&gt;', (string)$xml->item->markup);
    }

    public function testXmlModifiersKeepUtf8AndLiteralSeparatorsWithoutEscape(): void
    {
        $filter = $this->filter([['column' => 'title', 'operation' => 'uppercase']], [
            'output_params_delimiter' => ';', 'output_params_encoding' => 'ISO-8859-1',
            'filters_output_limit' => [['column' => 'title', 'limit' => 8]]
        ]);
        $value = $filter->cleanField('café; 東京ABC', ['column' => 'title']);
        self::assertSame('CAFÉ; 東京', $filter->modifyRows([['title' => $value]])[0]['title']);
    }

    public function testModifiersPreserveStructuredImageAndShippingBoundaries(): void
    {
        $filter = $this->filter([
            ['column' => 'additional_image_link', 'operation' => 'secure_url'],
            ['column' => 'shipping', 'operation' => 'uppercase']
        ]);
        $images = new MappedValue(
            ['http://a.test/a,b.jpg', 'http://a.test/c.jpg'],
            'http://a.test/a,b.jpg,http://a.test/c.jpg'
        );
        $shipping = new MappedValue([
            ['country' => 'US', 'region' => '', 'service' => 'fast, tracked: daily', 'price' => '5 USD']
        ], 'US::fast, tracked: daily:5 USD');
        $row = $filter->modifyRows([['additional_image_link' => $images, 'shipping' => $shipping]])[0];
        self::assertInstanceOf(MappedValue::class, $row['additional_image_link']);
        self::assertInstanceOf(MappedValue::class, $row['shipping']);
        self::assertSame('https://a.test/a,b.jpg,https://a.test/c.jpg', (string)$row['additional_image_link']);
        self::assertSame('US::FAST, TRACKED: DAILY:5 USD', (string)$row['shipping']);
        self::assertSame('http://a.test/a,b.jpg', $images->values[0], 'cached source is immutable');
        $writer = new Rss([
            'xml_header' => '<rss xmlns:g="http://base.google.com/ns/1.0"><channel>',
            'xml_item' => 'item', 'xml_footer' => '</channel></rss>',
            'xml_list_fields' => 'additional_image_link',
            'xml_compound_fields' => ['shipping' => ['country', 'region', 'service', 'price']]
        ], 'test', 'https://example.test');
        $doc = simplexml_load_string($writer->header() . $writer->row($row) . $writer->footer());
        self::assertInstanceOf(\SimpleXMLElement::class, $doc);
        $values = $doc->channel->item->children('http://base.google.com/ns/1.0');
        self::assertCount(2, $values->additional_image_link);
        self::assertSame('https://a.test/a,b.jpg', (string)$values->additional_image_link[0]);
        self::assertSame('https://a.test/c.jpg', (string)$values->additional_image_link[1]);
        self::assertSame('FAST, TRACKED: DAILY', (string)$values->shipping->service);
    }

    /**
     * @param list<array<string,string>> $rows
     * @param array<string,mixed> $config
     * @return Filter
     */
    private function filter(array $rows, array $config = []): Filter
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['type' => 'generic', 'config' => new DataObject($config + [
            'output_params_format' => 'xml', ModifierChain::CONFIG_KEY => $rows
        ])]);
        return (new Filter(new Cache()))->setFeed($feed);
    }
}
