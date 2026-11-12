<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Generator\Cache;
use MageOS\ShoppingFeed\Model\Product\Filter;
use MageOS\ShoppingFeed\Model\Product\ModifierChain;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for defects found by the SHO-5 independent test (2026-11-11).
 * Protect the output contracts exercised by the independent installed-store review.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ModifierRegressionTest extends TestCase
{
    /** @dataProvider markupWithBodies */
    #[DataProvider('markupWithBodies')]
    public function testExplicitStripTagsMatchesDefaultCleaningForScriptAndStyle(string $input, string $expected): void
    {
        $filter = $this->filter([['column' => 'plain', 'operation' => 'strip_tags', 'value' => '']]);
        $default = $filter->cleanField($input, ['column' => 'legacy']);
        self::assertSame($expected, $default, 'precondition: default cleaning output');
        $row = ['legacy' => $default, 'plain' => $filter->cleanField($input, ['column' => 'plain'])];
        self::assertSame($default, $filter->modifyRows([$row])[0]['plain']);
    }

    /** @return array<string, array<string>> */
    public static function markupWithBodies(): array
    {
        return [
            'script body' => ['<script>alert(1)</script>tail', 'tail'],
            'style body' => ['<style>p{color:red}</style>x', 'x'],
            'mixed case' => ['<STYLE type="text/css">p{color:red}</STYLE>x', 'x'],
            'unclosed script' => ['<script>alert(1)', ''],
        ];
    }

    public function testEscapeHtmlInUnenclosedSemicolonFeedNeverEmitsBrokenEntities(): void
    {
        $config = ['output_params_delimiter' => ';', 'output_params_enclose_cell' => ''];
        try {
            $filter = $this->filter([['column' => 'e', 'operation' => 'escape', 'value' => '']], $config);
            $value = (string)$filter->modifyRows([['e' => '<b>x</b> & y']])[0]['e'];
        } catch (LocalizedException) {
            // Rejecting Escape HTML for a feed that cannot carry ';' is an acceptable fix.
            $this->addToAssertionCount(1);
            return;
        }
        self::assertStringNotContainsString(';', $value, 'the delimiter must not leak into an unenclosed cell');
        self::assertDoesNotMatchRegularExpression(
            '/&(?:lt|gt|amp|quot|#0?39)(?!;)/',
            $value,
            'broken entity: ' . $value
        );
    }

    /** @dataProvider roundings */
    #[DataProvider('roundings')]
    public function testRoundReturnsPlainDecimalText(string $places, string $input, string $expected): void
    {
        $modifier = new ModifierChain();
        $chain = $modifier->compile([['column' => 'x', 'operation' => 'round', 'value' => $places]])['x'];
        self::assertSame($expected, $modifier->apply($input, $chain, 'USD'));
    }

    /** @return array<string, array<string>> */
    public static function roundings(): array
    {
        return [
            'no negative zero' => ['0', '-0.4', '0'],
            'no negative zero with suffix' => ['1', '-0.04 USD', '0 USD'],
            'no exponent for large values' => ['0', '123456789012345678', '123456789012345680'],
            'keeps requested digits' => ['9', '123456.123456789', '123456.123456789'],
            'keeps 12 requested places' => ['12', '1234.123456789012', '1234.123456789012'],
        ];
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, mixed> $config
     */
    private function filter(array $rows, array $config = []): Filter
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['id' => 1, 'type' => 'generic',
            'config' => new DataObject([ModifierChain::CONFIG_KEY => $rows] + $config)]);
        return (new Filter(new Cache()))->setFeed($feed);
    }
}
