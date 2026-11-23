<?php

namespace MageOS\ShoppingFeed\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\FeedFactory;
use MageOS\ShoppingFeed\Model\Feed\Source\Status as StatusSource;
use MageOS\ShoppingFeed\Ui\Component\Listing\Column\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class StatusTest extends TestCase
{
    /** @dataProvider progressMessages */
    #[DataProvider('progressMessages')]
    public function testProcessingStatusToleratesMissingOrInvalidProgress($messages, string $label): void
    {
        $feed = $this->createMock(Feed::class);
        $feed->method('setData')->willReturnSelf();
        $feed->method('getMessages')->willReturn($messages);
        $factory = $this->createMock(FeedFactory::class);
        $factory->method('create')->willReturn($feed);
        $source = $this->createMock(StatusSource::class);
        $source->method('getOptionArray')->willReturn([StatusSource::STATUS_PROCESSING => 'Processing']);
        $column = (new ObjectManager($this))->getObject(Status::class, [
            'sourceStatus' => $source, 'feedFactory' => $factory, 'escaper' => new Escaper(),
        ]);
        $column->setName('status');
        $result = $column->prepareDataSource(['data' => ['items' => [
            ['id' => 1, 'status' => StatusSource::STATUS_PROCESSING],
        ]]]);
        self::assertSame(
            '<span class="grid-severity-major shopping-feed-status-processing"><span>' . $label . '</span></span>',
            $result['data']['items'][0]['status']
        );
    }

    public static function progressMessages(): array
    {
        return [
            [[], 'Processing'], [false, 'Processing'],
            [['progress' => '<img src=x onerror=alert(1)>'], 'Processing'],
            [['progress' => []], 'Processing'], [['progress' => 120], 'Processing'],
            [['progress' => '0'], '0%'], [['progress' => '75'], '75%'],
        ];
    }
}
