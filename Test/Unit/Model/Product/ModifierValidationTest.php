<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Generator\Cache;
use MageOS\ShoppingFeed\Model\Product\Filter;
use MageOS\ShoppingFeed\Model\Product\ModifierChain;
use MageOS\ShoppingFeed\Observer\ValidateColumnModifiers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ModifierValidationTest extends TestCase
{
    /** @dataProvider conflictingDelimiters */
    #[DataProvider('conflictingDelimiters')]
    public function testEscapeRejectsUnsafeOutputOnSaveAndGeneration(string $delimiter): void
    {
        $feed = $this->feed('title', ['output_params_delimiter' => 'other',
            'output_params_delimiter_other' => $delimiter]);
        foreach (['save', 'generate'] as $boundary) {
            try {
                if ($boundary === 'save') {
                    $this->save($feed);
                } else {
                    (new Filter(new Cache()))->setFeed($feed)->modifyRows([['title' => '<b>A & B</b>']]);
                }
                self::fail($boundary . ' accepted an entity-conflicting delimiter.');
            } catch (LocalizedException $exception) {
                self::assertStringContainsString('cell enclosure', $exception->getMessage());
            }
        }
    }

    /** @return list<array{string}> */
    public static function conflictingDelimiters(): array
    {
        return [[';'], ['&'], ['#'], ['amp'], ['39'], ['&#'], ['lt;'], ['; '], [';|'], ['|&'], ['a|'], ['|9']];
    }

    public function testEnclosedSemicolonFeedPreservesLiteralAndGeneratedEntities(): void
    {
        $feed = $this->feed('title', ['output_params_delimiter' => ';', 'output_params_enclose_cell' => '"']);
        $this->save($feed);
        $filter = (new Filter(new Cache()))->setFeed($feed);
        $value = $filter->cleanField('<b>"Q" &amp; Co</b>', ['column' => 'title']);
        self::assertSame(
            [['title' => '&lt;b&gt;&quot;Q&quot; &amp; Co&lt;/b&gt;']],
            $filter->modifyRows([['title' => $value]])
        );
    }

    /** @dataProvider safeOutputFormats */
    #[DataProvider('safeOutputFormats')]
    public function testEscapedEntitiesRoundTripThroughTheWriter(string $delimiter, string $enclosure): void
    {
        $feed = $this->feed('title', [
            'output_params_delimiter' => $delimiter, 'output_params_enclose_cell' => $enclosure
        ]);
        $this->save($feed);
        $filter = (new Filter(new Cache()))->setFeed($feed);
        $source = $filter->cleanField('<b>"Q" &amp; Co</b>', ['column' => 'title']);
        $row = $filter->modifyRows([['title' => $source]])[0];
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
            self::assertSame(
                ['&lt;b&gt;&quot;Q&quot; &amp; Co&lt;/b&gt;'],
                str_getcsv(ltrim($output, "\n"), $delimiter, '"', '')
            );
        } finally {
            $generator->unsetData('temporary_handle');
            $driver->fileClose($handle);
        }
    }

    /** @return list<array{string, string}> */
    public static function safeOutputFormats(): array
    {
        return [[';', '"'], ['&', '"'], ['#', '"'], [',', ''], ["\t", ''], ['|', '']];
    }

    public function testNoEscapeKeepsExistingUnenclosedDelimiterBehavior(): void
    {
        $feed = $this->feed('title', ['output_params_delimiter' => ';']);
        $feed->getConfig()->setData(ModifierChain::CONFIG_KEY, [['column' => 'title', 'operation' => 'uppercase']]);
        $this->save($feed);
        $filter = (new Filter(new Cache()))->setFeed($feed);
        self::assertSame([['title' => 'A B']], $filter->modifyRows([['title' => 'a;b']]));
    }

    /** @dataProvider missingColumns */
    #[DataProvider('missingColumns')]
    public function testSaveRejectsUnknownColumnsWithoutChangingTheirNames(string $column): void
    {
        $feed = $this->feed($column);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('unknown output column');
        try {
            $this->save($feed);
        } finally {
            self::assertSame($column, $feed->getConfig(ModifierChain::CONFIG_KEY)[0]['column']);
        }
    }

    /** @return list<array{string}> */
    public static function missingColumns(): array
    {
        return [[' title '], ['typo'], ['TITLE']];
    }

    public function testSaveUsesSubmittedColumnsInsteadOfCachedMap(): void
    {
        $feed = $this->feed('promo');
        $feed->getColumnsMap();
        $feed->getConfig()->setData('columns_product_columns', [
            ['column' => 'promo', 'attribute' => 'directive_static_value', 'order' => 1]
        ]);
        $this->save($feed);
        self::assertSame('promo', $feed->getConfig(ModifierChain::CONFIG_KEY)[0]['column']);
    }

    public function testGoogleLegacyAndCanonicalNamesShareOneOrderedChain(): void
    {
        $feed = $this->feed('title');
        $feed->setData('type', 'google_shopping');
        $feed->getConfig()->setData('columns_product_columns', [
            ['column' => 'promotions_id', 'attribute' => 'directive_static_value', 'order' => 1]
        ])->setData(ModifierChain::CONFIG_KEY, [
            ['column' => 'promotions_id', 'operation' => 'append', 'value' => 'a'],
            ['column' => 'promotion_id', 'operation' => 'uppercase'],
            ['column' => 'promotions_id', 'operation' => 'append', 'value' => 'b']
        ]);
        $this->save($feed);
        self::assertSame(
            [['promotion_id' => 'PROMOAb']],
            (new Filter(new Cache()))->setFeed($feed)->modifyRows([['promotion_id' => 'promo']])
        );
    }

    public function testGoogleRuntimeVariantColumnCanBeSavedAndModified(): void
    {
        $feed = $this->feed('color');
        $feed->setData('type', 'google_shopping');
        $feed->getConfig()->setData('columns_product_columns', [
            ['column' => 'item_group_id', 'attribute' => 'directive_item_group_id', 'order' => 1]
        ])->setData(ModifierChain::CONFIG_KEY, [['column' => 'color', 'operation' => 'uppercase']]);
        $defaults = [];
        $variants = [
            'item_group_title', 'variant_option', 'color', 'size', 'material', 'pattern', 'gender', 'age_group'
        ];
        foreach ($variants as $i => $column) {
            $defaults[$column] = [
                'column' => $column, 'attribute' => 'directive_variant_attributes', 'order' => $i + 2
            ];
        }
        $types = $this->createMock(\MageOS\ShoppingFeed\Model\FeedTypes\Config::class);
        $types->method('getFeed')->willReturn([
            'default_feed_config' => ['columns' => ['product_columns' => $defaults]]
        ]);
        (new \ReflectionProperty(Feed::class, 'feedTypesConfig'))->setValue($feed, $types);
        $this->save($feed);
        self::assertContains('color', array_column($feed->getColumnsMap(), 'column'));
        self::assertSame(
            [['color' => 'RED']],
            (new Filter(new Cache()))->setFeed($feed)->modifyRows([['color' => 'red']])
        );
    }

    public function testScalarErrorDoesNotBlameTheRowLimit(): void
    {
        foreach ([false, true, 0, 'abc', ['key' => []]] as $value) {
            try {
                (new ModifierChain())->compile($value);
                self::fail('Expected a list-shape error.');
            } catch (LocalizedException $exception) {
                self::assertSame('Column modifiers must be a list of rows.', $exception->getMessage());
            }
        }
    }

    public function testOversizedListHasItsOwnError(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Column modifiers cannot exceed 200 rows.');
        (new ModifierChain())->compile(array_fill(0, 201, ['column' => 'title', 'operation' => 'escape']));
    }

    /**
     * @param string $column
     * @param array<string, mixed> $config
     * @return Feed
     */
    private function feed(string $column, array $config = []): Feed
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['id' => 1, 'type' => 'generic', 'config' => new DataObject($config + [
            'columns_product_columns' => [['column' => 'title', 'attribute' => 'name', 'order' => 1]],
            ModifierChain::CONFIG_KEY => [['column' => $column, 'operation' => 'escape']]
        ])]);
        return $feed;
    }

    /**
     * @param Feed $feed
     * @return void
     */
    private function save(Feed $feed): void
    {
        (new ValidateColumnModifiers(new ModifierChain()))->execute(new Observer([
            'event' => new Event(['object' => $feed])
        ]));
    }
}
