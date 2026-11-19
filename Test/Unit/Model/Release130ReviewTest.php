<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\ConditionalValue\Condition;
use MageOS\ShoppingFeed\Model\Output\Rss;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Regression tests for the 1.3.0 pull request review findings. */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class Release130ReviewTest extends TestCase
{
    /**
     * Messages built from admin input (column names) must go through the escaping message API.
     */
    public function testAdminControllersUseEscapingMessageApi(): void
    {
        $files = glob(dirname(__DIR__, 3) . '/Controller/Adminhtml/Feed/*.php') ?: [];
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            self::assertDoesNotMatchRegularExpression(
                '/messageManager->add(Error|Success|Warning|Notice|Exception)\(/',
                (string)file_get_contents($file),
                basename($file) . ' renders a message as raw HTML'
            );
        }
    }

    /**
     * Mass status changes must not revalidate stored rules; one drifted feed would stop the batch.
     *
     * @param class-string<\Magento\Backend\App\Action> $class
     * @param int $status
     * @dataProvider massStatusActions
     */
    #[DataProvider('massStatusActions')]
    public function testMassStatusActionsWriteStatusOnly(string $class, int $status): void
    {
        $feed = $this->createMock(\MageOS\ShoppingFeed\Model\Feed::class);
        $feed->method('getId')->willReturn(7);
        $feed->expects(self::never())->method('save');
        $feed->expects(self::never())->method('saveStatus');
        $resource = $this->createMock(\MageOS\ShoppingFeed\Model\ResourceModel\Feed::class);
        $resource->expects(self::once())->method('updateStatus')->with(7, $status)->willReturn(1);
        $events = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $events->expects(self::once())->method('dispatch')
            ->with('mageos_shopping_feed_feed_status_change', self::callback(
                static fn (array $data): bool => $data['feed'] === $feed && $data['status'] === $status
            ));
        $collection = $this->createMock(\MageOS\ShoppingFeed\Model\ResourceModel\Feed\Collection::class);
        $collection->method('getItems')->willReturn([$feed]);
        $filter = $this->createMock(\Magento\Ui\Component\MassAction\Filter::class);
        $filter->method('getCollection')->willReturn($collection);
        $queue = $this->createMock(\MageOS\ShoppingFeed\Model\ResourceModel\Generator\Queue\Collection::class);
        $queue->method('addFieldToFilter')->willReturnSelf();
        $queue->method('getItems')->willReturn([]);
        $queueFactory = $this->createMock(
            \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Queue\CollectionFactory::class
        );
        $queueFactory->method('create')->willReturn($queue);
        $redirect = $this->createMock(\Magento\Framework\Controller\Result\Redirect::class);
        $redirect->method('setPath')->willReturnSelf();
        $results = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);
        $results->method('create')->willReturn($redirect);
        $context = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $context->method('getResultFactory')->willReturn($results);
        $context->method('getEventManager')->willReturn($events);
        $context->method('getMessageManager')
            ->willReturn($this->createMock(\Magento\Framework\Message\ManagerInterface::class));
        $feeds = $this->createMock(\MageOS\ShoppingFeed\Model\ResourceModel\Feed\CollectionFactory::class);
        $feeds->method('create')->willReturn($collection);
        $arguments = [$context, $filter, $feeds];
        if ((new \ReflectionMethod($class, '__construct'))->getNumberOfParameters() === 5) {
            $arguments[] = $queueFactory;
        }
        $arguments[] = $resource;

        (new $class(...$arguments))->execute();
    }

    /** @return array<string, array{class-string, int}> */
    public static function massStatusActions(): array
    {
        return [
            'disable' => [
                \MageOS\ShoppingFeed\Controller\Adminhtml\Feed\MassDisable::class,
                \MageOS\ShoppingFeed\Model\Feed\Source\Status::STATUS_DISABLED,
            ],
            'enable' => [
                \MageOS\ShoppingFeed\Controller\Adminhtml\Feed\MassEnable::class,
                \MageOS\ShoppingFeed\Model\Feed\Source\Status::STATUS_SCHEDULED,
            ],
        ];
    }

    /**
     * A status change must not rewrite generator progress or any other column of a loaded feed.
     */
    public function testStatusUpdateWritesOnlyTheStatusColumn(): void
    {
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->expects(self::once())->method('update')
            ->with('mageos_shopping_feed_feed', ['status' => 0], ['id = ?' => 7])
            ->willReturn(1);
        $resource = $this->statusResource($connection);

        self::assertSame(1, $resource->updateStatus(7, 0));
    }

    public function testStatusUpdateRejectsAnUnknownStatus(): void
    {
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->expects(self::never())->method('update');
        $this->expectException(LocalizedException::class);

        $this->statusResource($connection)->updateStatus(7, 99);
    }

    private function statusResource(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection
    ): \MageOS\ShoppingFeed\Model\ResourceModel\Feed {
        $resources = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $resources->method('getConnection')->willReturn($connection);
        $resources->method('getTableName')->willReturnArgument(0);
        $context = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $context->method('getResources')->willReturn($resources);
        return new \MageOS\ShoppingFeed\Model\ResourceModel\Feed(
            $context,
            $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class)
        );
    }

    /**
     * A rule saved with "SKU" must read the product's "sku" data, not an empty "SKU" key.
     */
    public function testAttributeCodeIsStoredInItsCanonicalSpelling(): void
    {
        // Like Magento, the attribute found case-insensitively reports the spelling it was asked for.
        $attribute = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->disableOriginalConstructor()->onlyMethods(['getId', 'getAttributeCode'])->getMock();
        $attribute->method('getId')->willReturn(74);
        $attribute->method('getAttributeCode')->willReturn('SKU');
        $eavConfig = $this->createMock(\Magento\Eav\Model\Config::class);
        $eavConfig->method('getAttribute')->willReturn($attribute);
        $eavConfig->method('getEntityAttributeCodes')->willReturn(['name', 'sku', 'colorCode']);
        $configuration = new \MageOS\ShoppingFeed\Model\ConditionalValue\Configuration(
            $eavConfig,
            $this->createMock(\MageOS\ShoppingFeed\Model\ConditionalValue\ConditionFactory::class)
        );
        $validate = new \ReflectionMethod($configuration, 'validateOutput');

        $output = $validate->invoke($configuration, ['type' => 'attribute', 'attribute' => 'SKU']);

        self::assertSame('sku', $output['attribute']);
        $mixedCase = $validate->invoke($configuration, ['type' => 'attribute', 'attribute' => 'colorCode']);
        self::assertSame('colorCode', $mixedCase['attribute'], 'a mixed-case custom code keeps its spelling');
    }

    /**
     * A ModifierChain subclass whose constructor does not call the parent still strips tags.
     */
    public function testModifierChainSubclassWithoutParentConstructorStripsTags(): void
    {
        $chain = new class () extends \MageOS\ShoppingFeed\Model\Product\ModifierChain {
            public function __construct()
            {
            }
        };
        $stripTags = [['column' => 'title', 'operation' => 'strip_tags', 'value' => '']];
        $result = $chain->apply('<b>Red</b> shirt', $stripTags, 'USD');

        self::assertSame('Red shirt', $result);
    }

    /**
     * A reused feed object must not keep modifiers compiled for an earlier generation.
     */
    public function testFilterRecompilesModifiersForANewGeneration(): void
    {
        $chains = [['column' => 'title', 'operation' => 'uppercase', 'value' => '', 'order' => 1]];
        $feed = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Feed::class)
            ->disableOriginalConstructor()->onlyMethods(['getConfig', 'getColumnsMap'])->getMock();
        $feed->method('getConfig')->willReturnCallback(static fn (string $path, $default = null) => $default);
        $config = new \Magento\Framework\DataObject();
        $feed->setData('config', $config);
        $config->setData(\MageOS\ShoppingFeed\Model\Product\ModifierChain::CONFIG_KEY, $chains);
        $feed->method('getColumnsMap')->willReturn([['column' => 'title', 'attribute' => 'name']]);
        $filter = new \MageOS\ShoppingFeed\Model\Product\Filter(
            new \MageOS\ShoppingFeed\Model\Generator\Cache(),
            new \MageOS\ShoppingFeed\Model\Product\ModifierChain(),
            new \MageOS\ShoppingFeed\Model\Product\Markup()
        );

        $feed->setData('generation_run_id', 'first');
        $filter->setFeed($feed);
        self::assertSame('RED', $filter->modifyRows([['title' => 'Red']])[0]['title']);

        $chains[0]['operation'] = 'lowercase';
        $config->setData(\MageOS\ShoppingFeed\Model\Product\ModifierChain::CONFIG_KEY, $chains);
        $filter->setFeed($feed);
        self::assertSame('RED', $filter->modifyRows([['title' => 'Red']])[0]['title'], 'reused within a run');

        $feed->setData('generation_run_id', 'second');
        $filter->setFeed($feed);
        self::assertSame('red', $filter->modifyRows([['title' => 'Red']])[0]['title']);
    }

    /**
     * Bundle "Dynamic Price/SKU/Weight" are boolean attributes without a source model.
     */
    public function testBooleanAttributeWithoutSourceOffersYesNo(): void
    {
        $attribute = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->disableOriginalConstructor()->onlyMethods(['usesSource'])->getMock();
        $attribute->method('usesSource')->willReturn(false);
        $attribute->setData(['attribute_code' => 'price_type', 'frontend_input' => 'boolean']);
        $condition = $this->getMockBuilder(Condition::class)->disableOriginalConstructor()
            ->onlyMethods(['getAttributeObject'])->getMock();
        $condition->method('getAttributeObject')->willReturn($attribute);
        $condition->setAttribute('price_type');

        $options = $condition->getValueSelectOptions();

        self::assertSame(['1', '0'], array_column($options, 'value'));
    }

    /**
     * Two columns that resolve to one XML element would silently overwrite each other.
     *
     * @param list<string> $columns
     * @dataProvider duplicateXmlColumns
     */
    #[DataProvider('duplicateXmlColumns')]
    public function testXmlColumnsMappingToOneElementAreRejected(array $columns): void
    {
        $writer = new Rss(self::googleXmlConfig(), 'Store', 'https://example.com');
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('both map to the element');
        $writer->validateColumns($columns);
    }

    /** @return array<string, mixed> Shipped Google Shopping XML defaults */
    private static function googleXmlConfig(): array
    {
        $document = new \DOMDocument();
        $document->load(dirname(__DIR__, 3) . '/etc/mageos_shopping_feed.xml');
        $converter = new \MageOS\ShoppingFeed\Model\FeedTypes\Config\Converter(
            new \Magento\Framework\Json\Decoder(new \Magento\Framework\Serialize\Serializer\Json())
        );
        return $converter->convert($document)['feed']['google_shopping']['default_feed_config']['output_params'];
    }

    /** @return array<string, array{list<string>}> */
    public static function duplicateXmlColumns(): array
    {
        return [
            'prefixed twin' => [['id', 'title', 'g:title']],
            'alias and its element name' => [['id', 'g:price', 'price']],
        ];
    }

    /**
     * Repeating one column is how a repeatable element such as additional_image_link gets several values.
     */
    public function testRepeatingOneXmlColumnIsAllowed(): void
    {
        $writer = new Rss(self::googleXmlConfig(), 'Store', 'https://example.com');
        $writer->validateColumns(['id', 'additional_image_link', 'additional_image_link']);
        $this->addToAssertionCount(1);
    }

    public function testDistinctXmlColumnsStillValidate(): void
    {
        $writer = new Rss(self::googleXmlConfig(), 'Store', 'https://example.com');
        $writer->validateColumns(['id', 'title', 'price']);
        $this->addToAssertionCount(1);
    }
}
