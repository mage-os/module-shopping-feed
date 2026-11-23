<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model;

use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Generator;
use MageOS\ShoppingFeed\Model\Logger;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class GoogleAndCustomOutputTest extends TestCase
{
    /**
     * Prepare a generator with captured file writes.
     *
     * @phpstan-param array<string, string> $settings
     */
    private function writer(
        string $type,
        array $columns,
        string $delimiter,
        array &$writes,
        array $settings = []
    ): Generator {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getConfig', 'getColumnsMap', 'getXmlPreset'])->getMock();
        $feed->method('getData')->willReturn($type);
        $feed->method('getConfig')->willReturnCallback(fn($key, $default = '') =>
            $settings[$key] ?? ($key === 'output_params_delimiter' ? $delimiter : $default));
        $feed->method('getColumnsMap')->willReturn(array_map(fn($name) => ['column' => $name], $columns));
        $driver = $this->createMock(File::class);
        $driver->method('fileWrite')->willReturnCallback(function ($handle, $line) use (&$writes) {
            $writes[] = $line;
            return strlen($line);
        });
        $generator = $this->getMockBuilder(Generator::class)->disableOriginalConstructor()
            ->onlyMethods(['getLogger'])->getMock();
        $generator->method('getLogger')->willReturn($this->createMock(Logger::class));
        (new \ReflectionProperty(Generator::class, 'feed'))->setValue($generator, $feed);
        (new \ReflectionProperty(Generator::class, 'fileDriver'))->setValue($generator, $driver);
        $generator->setData('temporary_handle', 'handle');
        return $generator;
    }

    private function write(Generator $generator, array $row, bool $body = true): void
    {
        (new \ReflectionMethod(Generator::class, 'writeFeed'))->invoke($generator, $row, $body);
    }

    public function testCustomCsvRoundTripsQuotesCommasUnicodeAndEmptyCells(): void
    {
        $writes = [];
        $columns = ['custom,text', 'merchant_sku', 'empty'];
        $generator = $this->writer('generic', $columns, ',', $writes);
        $this->write($generator, array_combine($columns, $columns), false);
        foreach (['"Quoted" title, café 東京', '"Unclosed, title', 'backslash\\"quoted'] as $value) {
            $this->write($generator, ['custom,text' => $value, 'merchant_sku' => 'sku-1', 'empty' => '']);
            $this->assertSame([$value, 'sku-1', ''], str_getcsv(ltrim(end($writes), "\n"), ',', '"', ''));
        }
        $this->assertSame($columns, str_getcsv($writes[0], ',', '"', ''));
    }

    public function testXmlRowsUseMappedNamesAndDoNotWriteColumnHeaders(): void
    {
        $writes = [];
        $generator = $this->writer('generic', ['id', 'image', 'empty'], ',', $writes, [
            'output_params_format' => 'xml', 'output_params_xml_header' => '<products>',
            'output_params_xml_optional_columns' => 'empty',
        ]);
        $this->write($generator, ['id' => 'id', 'image' => 'image', 'empty' => 'empty'], false);
        $this->write($generator, ['id' => 0, 'image' => ['a&b', 'c<d'], 'empty' => '']);
        self::assertSame("<products>\n", $writes[0]);
        self::assertSame("<product><id>0</id><image>a&amp;b</image><image>c&lt;d</image></product>\n", $writes[1]);
        self::assertSame(1, $generator->getCountProductsExported());
    }

    public function testBadXmlRowsAreSkippedWithSkuAndColumnAndLaterRowsSurvive(): void
    {
        $writes = [];
        $generator = $this->writer(
            'generic',
            ['id', 'description'],
            ',',
            $writes,
            ['output_params_format' => 'xml']
        );
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getSku')->willReturn('source-sku');
        $adapter = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple::class);
        $adapter->method('getProduct')->willReturn($product);
        $adapter->method('map')->willReturn([
            ['id' => 'bad-child', 'description' => "bad\x0Btext"],
            ['id' => 'good-child', 'description' => 'good & text'],
        ]);
        $logger = $generator->getLogger();
        self::assertInstanceOf(\PHPUnit\Framework\MockObject\MockObject::class, $logger);
        $logger->expects(self::once())->method('warning')->with(self::callback(
            static fn ($message) => str_contains($message, 'source-sku')
                && str_contains($message, 'bad-child') && str_contains($message, 'description')
        ));
        (new \ReflectionMethod($generator, 'addProductToFeed'))->invoke($generator, $adapter);
        self::assertCount(1, $writes);
        self::assertStringContainsString('<id>good-child</id>', $writes[0]);
        self::assertSame(1, $generator->getCountProductsExported());
        self::assertSame(1, $generator->getCountProductsSkipped());
    }

    public function testInvalidXmlConfigurationStillStopsGeneration(): void
    {
        $writes = [];
        $generator = $this->writer(
            'generic',
            ['undeclared:id'],
            ',',
            $writes,
            ['output_params_format' => 'xml']
        );
        $adapter = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple::class);
        $adapter->method('map')->willReturn([['undeclared:id' => 'value']]);
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        (new \ReflectionMethod($generator, 'addProductToFeed'))->invoke($generator, $adapter);
    }

    /** @dataProvider dateCases */
    #[DataProvider('dateCases')]
    public function testGoogleBackordersRequireARealFutureDate(string $input, bool $valid): void
    {
        $writes = [];
        $generator = $this->writer('google_shopping', ['availability', 'availability_date', 'id'], "\t", $writes);
        $date = match ($input) {
            'future' => gmdate('c', time() + 86400 * 10),
            'database' => gmdate('Y-m-d H:i:s', time() + 86400 * 10),
            'bad_timezone' => gmdate('Y-m-d\\TH:i:s', time() + 86400 * 10) . '+99:00',
            'past' => gmdate('c', time() - 86400),
            'too_far' => gmdate('c', time() + 86400 * 400),
            default => $input,
        };
        $this->write($generator, ['availability' => 'backorder', 'availability_date' => $date, 'id' => 'p1']);
        $this->assertCount($valid ? 1 : 0, $writes);
        $this->assertSame($valid ? 1 : 0, $generator->getCountProductsExported());
        $this->assertSame($valid ? 0 : 1, $generator->getCountProductsSkipped());
        if ($valid) {
            $value = explode("\t", ltrim($writes[0], "\n"))[1];
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $value);
        }
    }

    public static function dateCases(): array
    {
        return [
            ['', false],
            ['tomorrow', false],
            ['2027-02-30', false],
            ['past', false],
            ['too_far', false],
            ['bad_timezone', false],
            ['future', true],
            ['database', true],
        ];
    }

    public function testCustomFeedsKeepTheirColumnOrderAndBackorderVocabulary(): void
    {
        $writes = [];
        $generator = $this->writer('generic', ['id', 'availability', 'price', 'sale_price'], "\t", $writes);
        $this->write($generator, ['id' => 'p1', 'availability' => 'backorder', 'price' => '10', 'sale_price' => '20']);
        $this->assertSame(["\np1\tbackorder\t10\t20"], $writes);
    }

    public function testOptionalGoogleAvailabilityDatesAreNormalizedOrOmitted(): void
    {
        $writes = [];
        $generator = $this->writer('google_shopping', ['availability', 'availability_date'], "\t", $writes);
        $future = gmdate('Y-m-d H:i:s', time() + 86400 * 10);
        $this->write($generator, ['availability' => 'in_stock', 'availability_date' => $future]);
        $this->write($generator, ['availability' => 'out_of_stock', 'availability_date' => 'tomorrow']);
        $this->assertSame("\nin_stock\t" . str_replace(' ', 'T', $future) . '+00:00', $writes[0]);
        $this->assertSame("\nout_of_stock\t", $writes[1]);
        $this->assertSame(2, $generator->getCountProductsExported());
        $this->assertSame(0, $generator->getCountProductsSkipped());
    }
}
