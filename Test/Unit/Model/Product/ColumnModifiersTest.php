<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product;

use Magento\Framework\DataObject;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Generator\Cache;
use MageOS\ShoppingFeed\Model\Product\Filter;
use MageOS\ShoppingFeed\Model\Product\ModifierChain;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ColumnModifiersTest extends TestCase
{
    public function testCleaningPreservesMarkupForExplicitChainAndLimitsFinalValue(): void
    {
        $filter = $this->filter([
            ['column' => 'escaped', 'operation' => 'escape'],
            ['column' => 'plain', 'operation' => 'strip_tags'],
            ['column' => 'title', 'operation' => 'prepend', 'value' => 'AB ']
        ], ['filters_output_limit' => [['column' => 'title', 'limit' => 6]]]);
        $row = [];
        foreach (['escaped', 'plain', 'legacy', 'title'] as $column) {
            $row[$column] = $filter->cleanField('<b>Hello</b>', ['column' => $column]);
        }
        self::assertSame('Hello', $row['legacy']);
        self::assertSame('<b>Hello</b>', $row['escaped']);
        self::assertSame('Hello', $row['title']);
        self::assertSame([[
            'escaped' => '&lt;b&gt;Hello&lt;/b&gt;', 'plain' => 'Hello', 'legacy' => 'Hello', 'title' => 'AB Hel'
        ]], $filter->modifyRows([$row]));
    }

    public function testRepeatedCellsSeparatorsAndUnmodifiedColumns(): void
    {
        $filter = $this->filter([
            ['column' => 'links', 'operation' => 'secure_url'],
            ['column' => 'title', 'operation' => 'append', 'value' => "\tline\nbreak"]
        ]);
        self::assertSame([[
            'links' => ['https://a.test', 'https://b.test'], 'title' => 'A line break', 'zero' => 0
        ]], $filter->modifyRows([['links' => ['http://a.test', '//b.test'], 'title' => 'A', 'zero' => 0]]));
    }

    public function testNoModifiersPreservesRowsExactlyAndLegacyLimit(): void
    {
        $filter = $this->filter([], ['filters_output_limit' => [['column' => 'title', 'limit' => 3]]]);
        self::assertSame('Hel', $filter->cleanField('Hello', ['column' => 'title']));
        $rows = [['number' => 1.25, 'empty' => null, 'repeated' => ['A', 'B']]];
        self::assertSame($rows, $filter->modifyRows($rows));
    }

    public function testModifiedValuesRoundTripThroughTheRealCsvWriter(): void
    {
        $feed = $this->feed([
            ModifierChain::CONFIG_KEY => [
                ['column' => 'title', 'operation' => 'append', 'value' => ', "café"'],
                ['column' => 'html', 'operation' => 'escape']
            ],
            'output_params_delimiter' => ',',
            'columns_product_columns' => [
                ['column' => 'title', 'attribute' => 'name', 'order' => 1],
                ['column' => 'html', 'attribute' => 'description', 'order' => 2]
            ]
        ]);
        $filter = (new Filter(new Cache()))->setFeed($feed);
        $row = $filter->modifyRows([['title' => 'Red', 'html' => '<b>A & B</b>']])[0];
        $generator = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Generator::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        (new \ReflectionProperty($generator, 'feed'))->setValue($generator, $feed);
        $driver = new \Magento\Framework\Filesystem\Driver\File();
        (new \ReflectionProperty($generator, 'fileDriver'))->setValue($generator, $driver);
        $handle = $driver->fileOpen('php://temp', 'w+');
        $generator->setData('temporary_handle', $handle);
        try {
            (new \ReflectionMethod($generator, 'writeFeed'))->invoke($generator, $row);
            rewind($handle);
            $output = stream_get_contents($handle);
            self::assertSame(['Red, "café"', '&lt;b&gt;A &amp; B&lt;/b&gt;'], str_getcsv(trim($output), ',', '"', ''));
        } finally {
            $generator->unsetData('temporary_handle');
            $driver->fileClose($handle);
        }
    }

    public function testMalformedStoredFalseIsRejectedAtGeneration(): void
    {
        $filter = (new Filter(new Cache()))->setFeed($this->feed([ModifierChain::CONFIG_KEY => false]));
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $filter->modifyRows([['x' => 'value']]);
    }

    public function testFeedBindingInvalidatesCompiledChains(): void
    {
        $filter = $this->filter([['column' => 'x', 'operation' => 'append', 'value' => 'first']]);
        self::assertSame([['x' => 'first']], $filter->modifyRows([['x' => '']]));
        $filter->setFeed($this->feed([]));
        self::assertSame([['x' => '']], $filter->modifyRows([['x' => '']]));
    }

    public function testAdapterAppliesChainAfterFormattingBeforeEmptyChecks(): void
    {
        $feed = $this->feed([ModifierChain::CONFIG_KEY => [
            ['column' => 'title', 'operation' => 'prepend', 'value' => 'prefix '],
            ['column' => 'price', 'operation' => 'price']
        ], 'filters_skip_column_empty' => ['title']]);
        $filter = (new Filter(new Cache()))->setFeed($feed);
        $adapter = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple::class)
            ->disableOriginalConstructor()->onlyMethods(['getAdditionalProcessors', 'filterAndFormatRows'])->getMock();
        $adapter->method('getAdditionalProcessors')->willReturn([]);
        $adapter->expects(self::once())->method('filterAndFormatRows')->with([
            ['title' => '', 'price' => 12.3], ['title' => 'child', 'price' => 2]
        ])->willReturn([['title' => '', 'price' => '12.30'], ['title' => 'child', 'price' => '2.00']]);
        foreach (['feed' => $feed, 'filter' => $filter, 'cache' => new Cache()] as $name => $value) {
            (new \ReflectionProperty($adapter, $name))->setValue($adapter, $value);
        }
        $adapter->setData('store_currency_code', 'CAD');
        $result = (new \ReflectionMethod($adapter, 'afterMap'))->invoke($adapter, [
            ['title' => '', 'price' => 12.3], ['title' => 'child', 'price' => 2]
        ]);
        self::assertSame([
            ['title' => 'prefix ', 'price' => '12.30 CAD'], ['title' => 'prefix child', 'price' => '2.00 CAD']
        ], $result);
    }

    public function testSaveReloadTransportPreservesOrderLiteralTextAndClearing(): void
    {
        $form = new \MageOS\ShoppingFeed\Model\Adminhtml\FeedFormData();
        $data = $form->decode($form->encodeForProvider(['config' => [ModifierChain::CONFIG_KEY => [
            ['column' => 'x', 'operation' => 'uppercase', 'position' => 2, 'record_id' => '1'],
            ['column' => 'x', 'operation' => 'prepend', 'value' => '${ literal } 0 ', 'position' => 1]
        ]]]));
        $expected = [
            ['column' => 'x', 'operation' => 'prepend', 'value' => '${ literal } 0 '],
            ['column' => 'x', 'operation' => 'uppercase']
        ];
        self::assertSame($expected, $data['config'][ModifierChain::CONFIG_KEY]);
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()
            ->onlyMethods(['getSchedules', 'getUploads'])->getMock();
        $feed->method('getSchedules')->willReturn([]);
        $feed->method('getUploads')->willReturn([]);
        $feed->setData('config', new DataObject($data['config']));
        $reopened = $form->project($feed, [ModifierChain::CONFIG_KEY]);
        self::assertSame(1, $reopened['config'][ModifierChain::CONFIG_KEY][0]['position']);
        $decoded = $form->decode($form->encodeForProvider($reopened));
        self::assertSame($expected, $decoded['config'][ModifierChain::CONFIG_KEY]);
        $feed->getConfig()->setData(ModifierChain::CONFIG_KEY, null);
        $observer = new \Magento\Framework\Event\Observer([
            'event' => new \Magento\Framework\Event(['object' => $feed])
        ]);
        (new \MageOS\ShoppingFeed\Observer\ValidateColumnModifiers(new ModifierChain()))->execute($observer);
        self::assertSame([], $feed->getConfig(ModifierChain::CONFIG_KEY, []));
    }

    public function testNonUtf8TextUsesTheOutputEncodingAndCharacterLimit(): void
    {
        $filter = $this->filter([
            ['column' => 'x', 'operation' => 'prepend', 'value' => 'café '],
            ['column' => 'x', 'operation' => 'uppercase']
        ], ['output_params_encoding' => 'ISO-8859-1', 'filters_output_limit' => [['column' => 'x', 'limit' => 6]]]);
        $value = mb_convert_encoding('déjà', 'ISO-8859-1', 'UTF-8');
        $result = $filter->modifyRows([['x' => $value]])[0]['x'];
        self::assertSame('CAFÉ D', mb_convert_encoding($result, 'UTF-8', 'ISO-8859-1'));
    }

    /**
     * @param array<array<string, mixed>> $rows
     * @param array<string, mixed> $config
     * @return Filter
     */
    private function filter(array $rows, array $config = []): Filter
    {
        return (new Filter(new Cache()))->setFeed($this->feed([ModifierChain::CONFIG_KEY => $rows] + $config));
    }

    /**
     * @param array<string, mixed> $config
     * @return Feed
     */
    private function feed(array $config): Feed
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['id' => 1, 'type' => 'generic', 'config' => new DataObject($config)]);
        return $feed;
    }
}
