<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product\Adapter\Type;

use MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract;
use MageOS\ShoppingFeed\Model\Product\Adapter\Type\Composite;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Generator;
use MageOS\ShoppingFeed\Model\Logger;
use MageOS\ShoppingFeed\Model\Logger\Handler\MemoryStream;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class CompositeDiagnosticsTest extends TestCase
{
    public function testEmptyColumnDiagnosticPreservesTheMappedRowIdentifier(): void
    {
        $adapter = (new \ReflectionClass(Composite::class))->newInstanceWithoutConstructor();
        $feed = $this->createMock(Feed::class);
        $feed->method('getConfig')->with('filters_skip_column_empty')->willReturn(['price']);
        (new \ReflectionProperty($adapter, 'feed'))->setValue($adapter, $feed);
        $memory = new MemoryStream();
        $logger = new Logger($this->createMock(\MageOS\ShoppingFeed\Model\Logger\HandlerFactory::class));
        $logger->pushHandler($memory);
        (new \ReflectionProperty($adapter, 'logger'))->setValue($adapter, $logger);
        $generator = $this->createMock(Generator::class);
        $generator->expects(self::once())->method('updateCountSkip')->with(1);
        $adapter->setData('generator', $generator);

        self::assertFalse((new \ReflectionMethod($adapter, 'checkEmptyColumns'))->invoke(
            $adapter,
            ['id' => 'WANDS-QA-GROUPED-01', 'price' => '']
        ));
        $log = $memory->getLogContent();
        self::assertIsString($log);
        self::assertStringContainsString(
            'product row id WANDS-QA-GROUPED-01 skipped - by product skip rule, has price empty.',
            $log
        );
    }

    public function testEverySkippedChildIsRetainedByReason(): void
    {
        $adapter = (new \ReflectionClass(Composite::class))->newInstanceWithoutConstructor();
        $adapter->setSkipProduct('out_of_stock', 10)->setSkipProduct('out_of_stock', 20)
            ->setSkipProduct('disabled', 30)->setSkipProduct('out_of_stock', 40);
        self::assertSame(
            ['out_of_stock' => [10, 20, 40], 'disabled' => [30]],
            (new \ReflectionProperty($adapter, 'skippedData'))->getValue($adapter)
        );
        self::assertTrue($adapter->getData('is_skipped'));
    }

    public function testUnknownInheritanceModeFailsWithAClearMessage(): void
    {
        $adapter = (new \ReflectionClass(Composite::class))->newInstanceWithoutConstructor();
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Unknown product inheritance mode');
        (new \ReflectionMethod($adapter, 'mapByInheritanceFrom'))->invoke(
            $adapter,
            $this->createMock(AdapterAbstract::class),
            ['column' => 'title'],
            'invalid'
        );
    }
}
