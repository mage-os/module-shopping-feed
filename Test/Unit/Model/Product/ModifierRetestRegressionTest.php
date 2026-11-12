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

/**
 * Regression tests for defects found by the SHO-5 independent retest (2026-11-11).
 * Protect the installed-store failures recorded in the independent retest.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ModifierRetestRegressionTest extends TestCase
{
    /**
     * Defect A: a multi-character delimiter that mixes entity characters with other characters passes
     * save validation, and the delimiter-to-space normalization then cuts entities apart.
     *
     * @dataProvider mixedEntityDelimiters
     */
    #[DataProvider('mixedEntityDelimiters')]
    public function testEscapeWithMixedEntityDelimiterIsRejectedOrKeepsEntitiesIntact(
        string $delimiter,
        string $source
    ): void {
        $feed = $this->feed([
            'output_params_delimiter' => 'other',
            'output_params_delimiter_other' => $delimiter,
            'output_params_enclose_cell' => '',
            ModifierChain::CONFIG_KEY => [['column' => 'title', 'operation' => 'escape', 'value' => '']],
        ]);
        try {
            $this->save($feed);
            $filter = (new Filter(new Cache()))->setFeed($feed);
            $clean = $filter->cleanField($source, ['column' => 'title']);
            $cell = (string)$filter->modifyRows([['title' => $clean]])[0]['title'];
        } catch (LocalizedException) {
            // Rejecting this configuration at save or generation is an acceptable fix.
            $this->addToAssertionCount(1);
            return;
        }
        self::assertStringNotContainsString($delimiter, $cell, 'the delimiter must not leak into an unenclosed cell');
        self::assertDoesNotMatchRegularExpression(
            '/&(?:lt|gt|amp|quot|#0?39)(?!;)|(?<!&)(?<![a-z#])(?:lt|gt|amp|quot);/',
            $cell,
            'broken entity in ' . json_encode($cell)
        );
    }

    /** @return array<string, array{string, string}> */
    public static function mixedEntityDelimiters(): array
    {
        return [
            'semicolon space' => ['; ', 'Tom & Jerry'],
            'semicolon pipe' => [';|', '<b>|x'],
            'pipe ampersand' => ['|&', 'x|<b>'],
        ];
    }

    /**
     * Defect B: Google feeds rename a stored legacy promotions_id column to promotion_id at runtime.
     * Save validates against the stored name, so the modifier is accepted but never applied.
     */
    public function testAcceptedGoogleModifierIsAppliedToTheRuntimeColumn(): void
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['id' => 1, 'type' => 'google_shopping', 'config' => new DataObject([
            'columns_product_columns' => [
                ['column' => 'id', 'attribute' => 'directive_id', 'order' => 1],
                ['column' => 'promotions_id', 'attribute' => 'directive_static_value',
                    'param' => 'PROMO1', 'order' => 2],
            ],
            ModifierChain::CONFIG_KEY => [['column' => 'promotions_id', 'operation' => 'lowercase', 'value' => '']],
        ])]);
        try {
            $this->save($feed);
        } catch (LocalizedException) {
            // Rejecting a name that will not exist in the output is an acceptable fix.
            $this->addToAssertionCount(1);
            return;
        }
        $runtime = (new \ReflectionMethod($feed, 'upgradeGoogleColumns'))->invoke(
            $feed,
            $feed->getConfig()->getData('columns_product_columns')
        );
        $row = [];
        foreach ($runtime as $mapping) {
            $row[$mapping['column']] = (string)($mapping['param'] ?? '');
        }
        $modified = (new Filter(new Cache()))->setFeed($feed)->modifyRows([$row])[0];
        self::assertSame('promo1', $modified['promotion_id'] ?? null, 'accepted modifier had no effect on the output');
    }

    /**
     * Finding C: explicit Strip tags joins words that the default cleaner keeps apart.
     *
     * @dataProvider blockMarkup
     */
    #[DataProvider('blockMarkup')]
    public function testExplicitStripTagsKeepsWordBoundariesOfDefaultCleaning(string $input): void
    {
        $feed = $this->feed([ModifierChain::CONFIG_KEY => [
            ['column' => 'plain', 'operation' => 'strip_tags', 'value' => '']
        ]]);
        $filter = (new Filter(new Cache()))->setFeed($feed);
        $default = $filter->cleanField($input, ['column' => 'legacy']);
        $row = ['legacy' => $default, 'plain' => $filter->cleanField($input, ['column' => 'plain'])];
        self::assertSame($default, $filter->modifyRows([$row])[0]['plain']);
    }

    /** @return array<string, array{string}> */
    public static function blockMarkup(): array
    {
        return [
            'paragraphs' => ['<p>alpha</p><p>beta</p>'],
            'line break' => ['gamma<br>delta'],
            'list items' => ['<li>e</li><li>f</li>'],
            'page builder text' => ['<div data-content-type="text"><p>Soft cotton tee.</p><p>Machine wash.</p></div>'],
            'comment' => ['a<!-- hidden -->b'],
        ];
    }

    /** @param array<string, mixed> $config */
    private function feed(array $config): Feed
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['id' => 1, 'type' => 'generic', 'config' => new DataObject($config + [
            'columns_product_columns' => [
                ['column' => 'title', 'attribute' => 'name', 'order' => 1],
                ['column' => 'plain', 'attribute' => 'description', 'order' => 2],
                ['column' => 'legacy', 'attribute' => 'description', 'order' => 3],
            ],
        ])]);
        return $feed;
    }

    private function save(Feed $feed): void
    {
        (new ValidateColumnModifiers(new ModifierChain()))->execute(new Observer([
            'event' => new Event(['object' => $feed]),
        ]));
    }
}
