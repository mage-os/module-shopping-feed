<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product;

use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\Product\ModifierChain;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ModifierChainTest extends TestCase
{
    /** @dataProvider transformations */
    #[DataProvider('transformations')]
    public function testTransformsValues(string $operation, string $argument, string $input, string $expected): void
    {
        $modifier = new ModifierChain();
        $chain = $modifier->compile([['column' => 'x', 'operation' => $operation, 'value' => $argument]])['x'];
        self::assertSame($expected, $modifier->apply($input, $chain, 'USD'));
    }

    /** @return array<array<string>> */
    public static function transformations(): array
    {
        return [
            ['prepend', 'Brand: ', 'Shoe', 'Brand: Shoe'],
            ['append', ' 0', 'Shoe', 'Shoe 0'],
            ['lowercase', '', 'CAFÉ 東京', 'café 東京'],
            ['uppercase', '', 'café 東京', 'CAFÉ 東京'],
            ['titlecase', '', 'café noir', 'Café Noir'],
            ['round', '1', '-12.35', '-12.4'],
            ['round', '0', '12.5 USD', '13 USD'],
            ['integer', '', '-12.9', '-12'],
            ['decimal', '3', '1.2', '1.200'],
            ['decimal', '0', '0', '0'],
            ['price', '', '12.345', '12.35 USD'],
            ['price', '', '12.3 EUR', '12.30 EUR'],
            ['price', 'GBP', '12.3 EUR', '12.30 GBP'],
            ['price', '', '', ''],
            ['integer', '', 'not a number', 'not a number'],
            ['decimal', '2', '1e999', '1e999'],
            ['price', '', '1,234', '1,234'],
            ['date', 'd/m/Y', '2028-02-29', '29/02/2028'],
            ['date', 'Y-m-d H:i:s P', '2026-11-11T12:30:40-05:00', '2026-11-11 12:30:40 -05:00'],
            ['date', 'c', '2026-11-11', '2026-11-11T00:00:00+00:00'],
            ['date', 'd/m/Y', '2026-02-29', '2026-02-29'],
            ['date', 'Y', 'tomorrow', 'tomorrow'],
            ['date', 'Y', '2026-11-11T12:30:40+99:99', '2026-11-11T12:30:40+99:99'],
            ['date', 'Y', '', ''],
            ['strip_tags', '', '<b>Red</b> &amp; blue', 'Red &amp; blue'],
            ['escape', '', '<b>Red</b> & blue', '&lt;b&gt;Red&lt;/b&gt; &amp; blue'],
            ['escape', '', '&lt;b&gt; &amp;', '&lt;b&gt; &amp;'],
            ['secure_url', '', 'HTTP://example.test/a?next=http://other.test',
                'https://example.test/a?next=http://other.test'],
            ['secure_url', '', '//example.test/a', 'https://example.test/a'],
            ['secure_url', '', 'https://example.test/a', 'https://example.test/a'],
            ['secure_url', '', 'ftp://example.test/a', 'ftp://example.test/a'],
            ['secure_url', '', '/http://example.test', '/http://example.test'],
            ['secure_url', '', 'text http://example.test', 'text http://example.test']
        ];
    }

    public function testOrderAndRepeatedValues(): void
    {
        $modifier = new ModifierChain();
        $chain = $modifier->compile([
            ['column' => 'x', 'operation' => 'append', 'value' => ' test'],
            ['column' => 'y', 'operation' => 'append', 'value' => 'untouched'],
            ['column' => 'x', 'operation' => 'uppercase'],
            ['column' => 'x', 'operation' => 'prepend', 'value' => 'prefix ']
        ])['x'];
        self::assertSame(['prefix ONE TEST', 'prefix TWO TEST'], $modifier->apply(['one', 'two'], $chain, 'USD'));
    }

    /** @dataProvider invalidConfiguration */
    #[DataProvider('invalidConfiguration')]
    public function testRejectsInvalidConfiguration(mixed $rows): void
    {
        $this->expectException(LocalizedException::class);
        (new ModifierChain())->compile($rows);
    }

    /** @return array<array<mixed>> */
    public static function invalidConfiguration(): array
    {
        return [
            [false], ['json'], [[3 => ['column' => 'x', 'operation' => 'append']]],
            [[['column' => '', 'operation' => 'append']]],
            [[['column' => 'x', 'operation' => 'system', 'value' => 'anything']]],
            [[['column' => 'x', 'operation' => 'append', 'value' => []]]],
            [[['column' => 'x', 'operation' => 'round', 'value' => '-1']]],
            [[['column' => 'x', 'operation' => 'decimal', 'value' => '13']]],
            [[['column' => 'x', 'operation' => 'round', 'value' => '1.5']]],
            [[['column' => 'x', 'operation' => 'date', 'value' => '']]],
            [[['column' => 'x', 'operation' => 'price', 'value' => 'usd']]],
            [[['column' => 'x', 'operation' => 'prepend', 'value' => str_repeat('x', 4097)]]],
            [array_fill(0, 201, ['column' => 'x', 'operation' => 'append'])]
        ];
    }
}
