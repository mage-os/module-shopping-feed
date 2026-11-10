<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\ConditionalValue;

use MageOS\ShoppingFeed\Model\ConditionalValue\Condition;
use MageOS\ShoppingFeed\Model\ConditionalValue\ConditionFactory;
use MageOS\ShoppingFeed\Model\ConditionalValue\Configuration;
use MageOS\ShoppingFeed\Model\Logger;
use MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple;
use MageOS\ShoppingFeed\Model\Product\Filter;
use MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\ConditionalValue;
use MageOS\ShoppingFeed\Test\Unit\CompatibilityTestCase;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\DataProvider;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ConditionalValueTest extends CompatibilityTestCase
{
    /** @var Configuration */
    private Configuration $configuration;
    /** @var ConditionalValue */
    private ConditionalValue $mapper;
    /** @var Product */
    private Product $product;
    /** @var ConditionFactory */
    private ConditionFactory $factory;

    protected function setUp(): void
    {
        $attributes = [];
        foreach (['sku', 'price', 'manufacturer', 'color', 'release_date', 'attribute_set_id'] as $code) {
            $attribute = $this->getMockBuilder(Attribute::class)->disableOriginalConstructor()
                ->onlyMethods([])->getMock();
            $attribute->setData([
                'id' => 1, 'attribute_id' => 1, 'attribute_code' => $code, 'frontend_label' => $code,
                'frontend_input' => ['manufacturer' => 'select', 'color' => 'multiselect',
                    'release_date' => 'date'][$code] ?? 'text',
                'backend_type' => $code === 'release_date' ? 'datetime' : 'varchar'
            ]);
            $attributes[$code] = $attribute;
        }
        $eav = $this->createMock(Config::class);
        $eav->method('getAttribute')->willReturnCallback(static fn ($entity, $code) =>
            $attributes[$code] ?? new \Magento\Framework\DataObject());
        $this->factory = $this->createMock(ConditionFactory::class);
        $this->factory->method('create')->willReturnCallback(function () use ($attributes) {
            // Keep Magento's real operators, value parsing and comparison methods.
            $condition = $this->getMockBuilder(Condition::class)->disableOriginalConstructor()
                ->onlyMethods(['getAttributeObject'])->getMock();
            $condition->method('getAttributeObject')->willReturnCallback(
                static fn () => $attributes[$condition->getAttribute()] ?? null
            );
            $condition->loadOperatorOptions();
            $condition->setData('value_option', ['1' => 'One']);
            $condition->setData('value_select_options', array_map(
                static fn ($value) => ['value' => $value, 'label' => $value],
                ['0', '1', '2', '3', '4', '12', 'Acme', 'Other']
            ));
            return $condition;
        });
        $this->configuration = new Configuration($eav, $this->factory);
        $this->product = $this->getMockBuilder(Product::class)->disableOriginalConstructor()
            ->onlyMethods(['getResource', 'load'])->getMock();
        $resource = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product::class);
        $resource->method('getAttribute')->willReturnCallback(static fn ($code) => $attributes[$code] ?? false);
        $this->product->method('getResource')->willReturn($resource);
        $this->product->expects(self::never())->method('load');
        $this->product->setData(['entity_id' => 7, 'sku' => 'shirt', 'price' => '100',
            'manufacturer' => '12', 'color' => '2,3', 'attribute_set_id' => '4', 'store_id' => 2]);
        $adapter = $this->createMock(Simple::class);
        $adapter->method('getProduct')->willReturn($this->product);
        $adapter->method('getMapAttribute')->willReturnCallback(static fn ($row) => $attributes[$row['attribute']]);
        $adapter->method('getAttributeValue')->willReturn('Store-view brand');
        $filter = $this->createMock(Filter::class);
        $filter->method('cleanField')->willReturnCallback(static fn ($value) => $value);
        $adapter->method('getFilter')->willReturn($filter);
        $matcher = new \MageOS\ShoppingFeed\Model\ConditionalValue\Matcher($this->configuration, $this->factory);
        $this->mapper = new ConditionalValue($this->createMock(Logger::class), $this->configuration, $matcher);
        $this->mapper->addAdapter($adapter);
    }

    /** @return array<string,mixed> */
    private function expression(): array
    {
        return [
            'aggregator' => 'all',
            'conditions' => [['attribute' => 'price', 'operator' => '>', 'value' => '50']],
            'if_true' => ['type' => 'static', 'value' => 'premium'],
            'if_false' => ['type' => 'static', 'value' => '0']
        ];
    }

    public function testAllAndAnyEvaluateEveryProductWithoutReusingPreviousResult(): void
    {
        $value = $this->expression();
        $value['conditions'][] = ['attribute' => 'attribute_set_id', 'operator' => '==', 'value' => '4'];
        self::assertSame('premium', $this->mapper->map(['param' => $value]));
        $this->product->setData('attribute_set_id', '5');
        self::assertSame('0', $this->mapper->map(['param' => $value]));
        $value['aggregator'] = 'any';
        self::assertSame('premium', $this->mapper->map(['param' => $value]));
        $this->product->setData('price', 0);
        self::assertSame('0', $this->mapper->map(['param' => $value]));
    }

    public function testNativeMultiselectAndMissingValueSemantics(): void
    {
        $value = $this->expression();
        $value['conditions'] = [['attribute' => 'color', 'operator' => '()', 'value' => '3,4']];
        self::assertSame('premium', $this->mapper->map(['param' => $value]));
        $value['conditions'] = [['attribute' => 'manufacturer', 'operator' => '<=>', 'value' => '']];
        $this->product->unsetData('manufacturer');
        self::assertSame('premium', $this->mapper->map(['param' => $value]));
        $value['conditions'][0]['operator'] = '!=';
        $value['conditions'][0]['value'] = '12';
        self::assertSame('0', $this->mapper->map(['param' => $value]));
    }

    public function testLabelsAndPercentagesAndBlankSources(): void
    {
        $value = $this->expression();
        $value['if_true'] = ['type' => 'attribute', 'attribute' => 'manufacturer'];
        self::assertSame('Store-view brand', $this->mapper->map(['param' => $value]));
        $value['if_true'] = ['type' => 'percent', 'attribute' => 'price', 'percent' => '-10'];
        self::assertSame('90.00', $this->mapper->map(['param' => $value]));
        $value['if_true']['percent'] = 10;
        self::assertSame('110.00', $this->mapper->map(['param' => $value]));
        $value['conditions'] = [['attribute' => 'sku', 'operator' => '==', 'value' => 'shirt']];
        $this->product->setData('price', 0);
        self::assertSame('0.00', $this->mapper->map(['param' => $value]));
        $this->product->unsetData('price');
        self::assertSame('', $this->mapper->map(['param' => $value]));
    }

    public function testDateComparisonDoesNotMutateProduct(): void
    {
        $value = $this->expression();
        $value['conditions'] = [['attribute' => 'release_date', 'operator' => '>=', 'value' => '2026-11-01']];
        $this->product->setData('release_date', '2026-11-09 00:00:00');
        self::assertSame('premium', $this->mapper->map(['param' => $value]));
        self::assertSame('2026-11-09 00:00:00', $this->product->getData('release_date'));
    }

    public function testBothMappingLocationsRejectMalformedSavedParameters(): void
    {
        foreach (['columns_product_columns', 'filters_map_replace_empty_columns'] as $key) {
            $feed = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Feed::class)
                ->disableOriginalConstructor()->onlyMethods(['getConfig'])->getMock();
            $feed->method('getConfig')->willReturnCallback(static fn ($path) => $path === $key
                ? [['attribute' => Configuration::DIRECTIVE, 'param' => []]] : []);
            $observer = new \MageOS\ShoppingFeed\Observer\ValidateConditionalValues($this->configuration);
            try {
                $observer->execute(new \Magento\Framework\Event\Observer([
                    'event' => new \Magento\Framework\Event(['object' => $feed])
                ]));
                self::fail('Invalid saved conditional parameters were accepted.');
            } catch (LocalizedException $exception) {
                self::assertStringContainsString('between 1 and 50', $exception->getMessage());
            }
        }
    }

    public function testFormEnvelopeRoundTripPreservesExpressionAndOutput(): void
    {
        $form = new \MageOS\ShoppingFeed\Model\Adminhtml\FeedFormData();
        $value = $this->expression();
        $value['if_true']['value'] = '0 ${literal} <b>text</b>';
        $decoded = $form->decode($form->encodeForProvider(['config' => [
            'columns_product_columns' => [['column' => 'custom_label_0',
                'attribute' => Configuration::DIRECTIVE, 'param' => $value]]
        ]]));
        $row = $decoded['config']['columns_product_columns'][0];
        self::assertSame($value, $row['param']);
        self::assertSame($value['if_true']['value'], $this->mapper->map($row));
    }

    public function testAllFeedTypesExposeDirectiveAndSaveObserverIsRegistered(): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 4) . '/etc/mageos_shopping_feed.xml');
        self::assertInstanceOf(\SimpleXMLElement::class, $xml);
        foreach ($xml->feed as $feed) {
            self::assertCount(1, $feed->xpath('directives/directive[@name="directive_conditional_value"]') ?? []);
        }
        $events = simplexml_load_file(dirname(__DIR__, 4) . '/etc/events.xml');
        self::assertInstanceOf(\SimpleXMLElement::class, $events);
        self::assertCount(1, $events->xpath('event[@name="mageos_shopping_feed_feed_save_before"]/observer'
            . '[@name="mageos_shopping_feed_validate_conditional_values"]') ?? []);
    }

    public function testNonnumericPercentageSourceStopsGeneration(): void
    {
        $value = $this->expression();
        $value['if_true'] = ['type' => 'percent', 'attribute' => 'sku', 'percent' => 10];
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('must be numeric');
        $this->mapper->map(['param' => $value]);
    }

    /**
     * @dataProvider invalidConfigurations
     * @param array<string,mixed> $change
     */
    #[DataProvider('invalidConfigurations')]
    public function testRejectsInvalidConfiguration(array $change): void
    {
        $this->expectException(LocalizedException::class);
        $this->configuration->validate(array_replace($this->expression(), $change));
    }

    /** @return list<array{array<string,mixed>}> */
    public static function invalidConfigurations(): array
    {
        return [
            [['conditions' => []]],
            [['aggregator' => 'xor']],
            [['conditions' => [['attribute' => 'missing', 'operator' => '==', 'value' => '1']]]],
            [['conditions' => [['attribute' => 'price', 'operator' => 'eval', 'value' => '1']]]],
            [['conditions' => [['attribute' => 'price', 'operator' => '==', 'value' => []]]]],
            [['if_false' => ['type' => 'percent', 'attribute' => 'price', 'percent' => '-101']]],
            [['if_false' => ['type' => 'percent', 'attribute' => 'price', 'percent' => '1e999']]],
            [['if_false' => ['type' => 'directive', 'attribute' => 'directive_conditional_value']]],
            [['conditions' => [['attribute' => 'release_date', 'operator' => '==', 'value' => '2026-02-30']]]]
        ];
    }
}
