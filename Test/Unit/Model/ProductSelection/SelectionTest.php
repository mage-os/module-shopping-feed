<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\ProductSelection;

use MageOS\ShoppingFeed\Model\ConditionalValue\Condition;
use MageOS\ShoppingFeed\Model\ConditionalValue\ConditionFactory;
use MageOS\ShoppingFeed\Model\ConditionalValue\Configuration;
use MageOS\ShoppingFeed\Model\ConditionalValue\Matcher;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\ProductSelection\Parents;
use MageOS\ShoppingFeed\Model\ProductSelection\Selection;
use MageOS\ShoppingFeed\Test\Unit\CompatibilityTestCase;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\DataProvider;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class SelectionTest extends CompatibilityTestCase
{
    /** @var Selection */
    private Selection $selection;
    /** @var Parents&\PHPUnit\Framework\MockObject\MockObject */
    private Parents $parents;
    /** @var Feed */
    private Feed $feed;
    /** @var Product */
    private Product $product;

    protected function setUp(): void
    {
        $attributes = [];
        $codes = [
            'sku', 'price', 'image', 'status', 'visibility', 'brand', 'color',
            'release_date', 'release_time', 'tier_price'
        ];
        foreach ($codes as $code) {
            $attribute = $this->getMockBuilder(Attribute::class)->disableOriginalConstructor()
                ->onlyMethods([])->getMock();
            $attribute->setData(['id' => 1, 'attribute_id' => 1, 'attribute_code' => $code, 'frontend_label' => $code,
                'frontend_input' => [
                    'brand' => 'select', 'color' => 'multiselect',
                    'release_date' => 'date', 'release_time' => 'datetime'
                ][$code] ?? 'text',
                'backend_type' => $code === 'release_date' ? 'datetime' : 'varchar']);
            $attributes[$code] = $attribute;
        }
        $eav = $this->createMock(Config::class);
        $eav->method('getAttribute')->willReturnCallback(static fn ($entity, $code) =>
            $attributes[$code] ?? new \Magento\Framework\DataObject());
        $factory = $this->createMock(ConditionFactory::class);
        $factory->method('create')->willReturnCallback(function () use ($attributes) {
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
        $configuration = new Configuration($eav, $factory);
        $this->parents = $this->createMock(Parents::class);
        $this->selection = new Selection($configuration, new Matcher($configuration, $factory), $this->parents);
        $this->feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $this->feed->setData(['store_id' => 2, 'config' => new \Magento\Framework\DataObject()]);
        $this->product = $this->product([
            'sku' => 'SHIRT-red-01', 'price' => '100', 'visibility' => '4', 'status' => '1'
        ]);
    }

    /** @param array<string, mixed> $data */
    private function product(array $data): Product
    {
        $product = $this->getMockBuilder(Product::class)->disableOriginalConstructor()
            ->onlyMethods(['load'])->getMock();
        $product->expects(self::never())->method('load');
        return $product->setData($data + ['store_id' => 2]);
    }

    /** @param list<array<string, mixed>> $rows */
    private function rules(array $rows, string $aggregator = 'all'): void
    {
        $this->feed->getConfig()->setData(Selection::CONFIG_KEY, ['aggregator' => $aggregator, 'conditions' => $rows]);
    }

    public function testGenerationResetsParentDataAndRequestsOnlyPredicateAttributes(): void
    {
        $this->rules([
            ['scope' => 'parent', 'attribute' => 'has_image', 'operator' => '==', 'value' => '1'],
            ['scope' => 'parent', 'attribute' => 'sku', 'operator' => 'glob', 'value' => '*'],
            ['attribute' => 'price', 'operator' => '>', 'value' => '10']
        ]);
        $this->parents->expects(self::once())->method('reset')->with(['image', 'sku']);
        $this->selection->beginRun($this->feed->getData('config', Selection::CONFIG_KEY));
    }

    public function testEmptyRulesPreserveEligibilityAndDoNotQueryParents(): void
    {
        $this->parents->expects(self::never())->method('get');
        foreach ([null, '', [], ['aggregator' => 'any', 'conditions' => []]] as $value) {
            $this->feed->getConfig()->setData(Selection::CONFIG_KEY, $value);
            self::assertFalse($this->selection->isExcluded($this->feed, $this->product));
        }
    }

    public function testAllAnyAndRepeatedProductsUseCurrentStoreData(): void
    {
        $rows = [['attribute' => 'price', 'operator' => '>=', 'value' => '75'],
            ['attribute' => 'visibility', 'operator' => '==', 'value' => '4']];
        $this->rules($rows);
        self::assertFalse($this->selection->isExcluded($this->feed, $this->product));
        $this->product->setData('price', '40');
        self::assertTrue($this->selection->isExcluded($this->feed, $this->product));
        $this->rules($rows, 'any');
        self::assertFalse($this->selection->isExcluded($this->feed, $this->product));
        $this->product->setData('visibility', '1');
        self::assertTrue($this->selection->isExcluded($this->feed, $this->product));
    }

    public function testSkuWildcardsAreCaseSensitiveAndTreatRegexCharactersLiterally(): void
    {
        $patterns = ['SHIRT-*-0?' => true, 'shirt-*' => false, 'SHIRT-(red)-*' => false, '*red*' => true];
        foreach ($patterns as $pattern => $matches) {
            foreach (['glob', '!glob'] as $operator) {
                $this->rules([['attribute' => 'sku', 'operator' => $operator, 'value' => $pattern]]);
                self::assertSame(
                    $operator === 'glob' ? !$matches : $matches,
                    $this->selection->isExcluded($this->feed, $this->product)
                );
            }
        }
    }

    public function testWildcardQuestionMarkMatchesOneUnicodeCharacter(): void
    {
        $this->rules([['attribute' => 'sku', 'operator' => 'glob', 'value' => 'sku-?']]);
        foreach (['é', '猫', '😀'] as $character) {
            $this->product->setData('sku', 'sku-' . $character);
            self::assertFalse($this->selection->isExcluded($this->feed, $this->product));
        }
        $this->product->setData('sku', 'sku-ab');
        self::assertTrue($this->selection->isExcluded($this->feed, $this->product));
    }

    public function testDateAndTimeAttributeUsesCalendarDayComparisons(): void
    {
        $this->product->setData('release_time', '2026-03-01 15:30:00');
        foreach (['==' => false, '<=' => false, '>=' => false] as $operator => $excluded) {
            $this->rules([['attribute' => 'release_time', 'operator' => $operator, 'value' => '2026-03-01']]);
            self::assertSame($excluded, $this->selection->isExcluded($this->feed, $this->product));
        }
    }

    public function testNullSelectionIsNormalizedBeforePersistence(): void
    {
        $this->feed->getConfig()->setData(Selection::CONFIG_KEY, null);
        $observer = new \MageOS\ShoppingFeed\Observer\ValidateProductSelection($this->selection);
        $observer->execute(new \Magento\Framework\Event\Observer([
            'event' => new \Magento\Framework\Event(['object' => $this->feed])
        ]));
        self::assertSame([], $this->feed->getData('config', Selection::CONFIG_KEY));
    }

    public function testImagePresenceHandlesMagentoEmptySentinels(): void
    {
        $this->rules([['attribute' => 'has_image', 'operator' => '==', 'value' => '1']]);
        foreach ([null, '', 'no_selection', ' ', '/s/h/shirt.jpg'] as $image) {
            $this->product->setData('image', $image);
            self::assertSame($image !== '/s/h/shirt.jpg', $this->selection->isExcluded($this->feed, $this->product));
        }
    }

    public function testMissingParentNeverMatchesEvenUndefinedOrNotEqual(): void
    {
        $this->parents->method('get')->willReturn([]);
        foreach (['<=>', '!='] as $operator) {
            $this->rules([['scope' => 'parent', 'attribute' => 'brand', 'operator' => $operator, 'value' => 'Acme']]);
            self::assertTrue($this->selection->isExcluded($this->feed, $this->product));
        }
        $this->rules([['scope' => 'parent', 'attribute' => 'brand', 'operator' => '==', 'value' => 'Acme'],
            ['attribute' => 'sku', 'operator' => '{}', 'value' => 'red']], 'any');
        self::assertFalse($this->selection->isExcluded($this->feed, $this->product));
    }

    public function testParentConditionsMustMatchTheSameParentIncludingDisabledStatus(): void
    {
        $first = $this->product(['brand' => 'Acme', 'status' => '1']);
        $second = $this->product(['brand' => 'Other', 'status' => '2']);
        $this->parents->expects(self::exactly(2))->method('get')->with($this->product, 2)
            ->willReturn([$first, $second]);
        $this->rules([['scope' => 'parent', 'attribute' => 'brand', 'operator' => '==', 'value' => 'Acme'],
            ['scope' => 'parent', 'attribute' => 'status', 'operator' => '==', 'value' => '2']]);
        self::assertTrue($this->selection->isExcluded($this->feed, $this->product));
        $first->setData('status', '2');
        self::assertFalse($this->selection->isExcluded($this->feed, $this->product));
        // An explicit generation context must not use a different linked parent to satisfy a rule.
        self::assertTrue($this->selection->isExcluded($this->feed, $this->product, $second));
        self::assertFalse($this->selection->isExcluded($this->feed, $this->product, $first));
    }

    public function testMultiselectDateAndMissingAttributeSemantics(): void
    {
        $this->product->setData('color', '2,3')->setData('release_date', '2026-11-10 00:00:00');
        $this->rules([['attribute' => 'color', 'operator' => '()', 'value' => '3,4'],
            ['attribute' => 'release_date', 'operator' => '>=', 'value' => '2026-11-01']]);
        self::assertFalse($this->selection->isExcluded($this->feed, $this->product));
        $this->rules([['attribute' => 'brand', 'operator' => '!=', 'value' => 'Acme']]);
        self::assertTrue($this->selection->isExcluded($this->feed, $this->product));
        $this->rules([['attribute' => 'brand', 'operator' => '<=>', 'value' => '']]);
        self::assertFalse($this->selection->isExcluded($this->feed, $this->product));
        self::assertSame('2026-11-10 00:00:00', $this->product->getData('release_date'));
    }

    public function testSimpleSelectionPrecedesDuplicateTracking(): void
    {
        $this->rules([['attribute' => 'price', 'operator' => '>', 'value' => '75']]);
        $this->feed->getConfig()->setData('columns_product_columns', [
            ['column' => 'sku', 'attribute' => 'sku', 'order' => 1]
        ]);
        $adapter = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple::class)
            ->disableOriginalConstructor()->onlyMethods(['isDuplicate', 'getMapValue'])->getMock();
        $this->setProperty($adapter, 'feed', $this->feed);
        $this->setProperty($adapter, 'product', $this->product);
        $this->setProperty($adapter, 'productSelection', $this->selection);
        $generator = $this->createMock(\MageOS\ShoppingFeed\Model\Generator::class);
        $generator->expects(self::once())->method('addSelectionExclusion');
        $adapter->setData('generator', $generator);
        $adapter->expects(self::once())->method('isDuplicate')->willReturn(false);
        $adapter->expects(self::once())->method('getMapValue')->willReturn('SHIRT-red-01');
        $this->product->setData('price', '40');
        self::assertSame([], $adapter->internalMap());
        $this->product->setData('price', '100');
        self::assertSame([['sku' => 'SHIRT-red-01']], $adapter->internalMap());
    }

    public function testCompositeParentFailureDoesNotSuppressMatchingChildren(): void
    {
        $this->rules([['attribute' => 'sku', 'operator' => '==', 'value' => 'child']]);
        $this->feed->getConfig()->setData('columns_product_columns', [
            ['column' => 'sku', 'attribute' => 'sku', 'order' => 1]
        ]);
        foreach (['Configurable', 'Grouped', 'Bundle'] as $type) {
            $parent = $this->getMockBuilder('MageOS\\ShoppingFeed\\Model\\Product\\Adapter\\Type\\' . $type)
                ->disableOriginalConstructor()
                ->onlyMethods(['getAssociatedProductsMode', 'mapByInheritance', 'getMapValue'])->getMock();
            $parent->method('getAssociatedProductsMode')->willReturn(2);
            $parent->method('mapByInheritance')->willReturn(false);
            $parent->expects(self::never())->method('getMapValue');
            $this->setProperty($parent, 'feed', $this->feed);
            $this->setProperty($parent, 'product', $this->product);
            $this->setProperty($parent, 'productSelection', $this->selection);
            $this->setProperty($parent, 'feedTypesConfig', $this->createMock(
                \MageOS\ShoppingFeed\Model\FeedTypes\Config::class
            ));
            $generator = $this->createMock(\MageOS\ShoppingFeed\Model\Generator::class);
            $parent->setData('generator', $generator);
            $child = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Product\Adapter\Type\Simple::class)
                ->disableOriginalConstructor()->onlyMethods(['isSkipped', 'isDuplicate', 'getMapValue'])->getMock();
            $this->setProperty($child, 'feed', $this->feed);
            $this->setProperty($child, 'product', $this->product(['sku' => 'child']));
            $child->setData('parent_adapter', $parent)->setData('generator', $generator);
            $this->setProperty($child, 'productSelection', $this->selection);
            $child->method('isSkipped')->willReturn(false);
            $child->expects(self::once())->method('isDuplicate')->willReturn(false);
            $child->expects(self::once())->method('getMapValue')->willReturn('child');
            $parent->setData('associated_product_adapters', [$child]);
            self::assertSame([['sku' => 'child']], $parent->internalMap());
            $childProduct = $child->getProduct();
            self::assertNotNull($childProduct);
            $childProduct->setData('sku', 'excluded-child');
            self::assertSame([], $parent->internalMap());
        }
    }

    /** Set constructor dependencies while leaving the adapter methods under test real. */
    private function setProperty(object $object, string $name, mixed $value): void
    {
        (new \ReflectionProperty($object, $name))->setValue($object, $value);
    }

    public function testMalformedFalseValueIsNotTreatedAsAnUnconfiguredFeed(): void
    {
        $this->feed->getConfig()->setData(Selection::CONFIG_KEY, false);
        $this->expectException(LocalizedException::class);
        $this->selection->isExcluded($this->feed, $this->product);
    }

    /** @dataProvider invalidRules */
    #[DataProvider('invalidRules')]
    public function testMalformedRulesFailClosed(mixed $value): void
    {
        $this->expectException(LocalizedException::class);
        $this->selection->validate($value);
    }

    /** @return list<array{mixed}> */
    public static function invalidRules(): array
    {
        $rule = ['attribute' => 'sku', 'operator' => '==', 'value' => '0'];
        $values = [false, 'bad', ['aggregator' => 'xor', 'conditions' => []]];
        $changes = [['scope' => 'unknown'], ['attribute' => 'no_such_attribute'], ['operator' => 'eval'],
            ['value' => []], ['value' => str_repeat('x', 4097)], ['attribute' => 'has_image', 'value' => 'yes'],
            ['attribute' => 'price', 'operator' => 'glob'], ['attribute' => 'tier_price'],
            ['attribute' => 'brand', 'value' => 'Xray'], ['attribute' => 'release_time', 'value' => 'not a date'],
            ['attribute' => 'release_date', 'value' => '2026-02-30']];
        foreach ($changes as $change) {
            $values[] = ['aggregator' => 'all', 'conditions' => [array_replace($rule, $change)]];
        }
        $values[] = ['aggregator' => 'all', 'conditions' => ['named' => $rule]];
        $values[] = ['aggregator' => 'all', 'conditions' => array_fill(0, 51, $rule)];
        return array_values(array_map(static fn ($value) => [$value], $values));
    }

    public function testSaveObserverAndFormEnvelopePreserveAndValidateRules(): void
    {
        $this->rules([['scope' => 'parent', 'attribute' => 'sku', 'operator' => 'glob', 'value' => '*${literal}*']]);
        $form = new \MageOS\ShoppingFeed\Model\Adminhtml\FeedFormData();
        $data = ['config' => $this->feed->getConfig()->getData()];
        self::assertSame($data['config'], $form->decode($form->encodeForProvider($data))['config']);
        $observer = new \MageOS\ShoppingFeed\Observer\ValidateProductSelection($this->selection);
        $event = new \Magento\Framework\Event\Observer([
            'event' => new \Magento\Framework\Event(['object' => $this->feed])
        ]);
        $observer->execute($event);
        $this->feed->getConfig()->setData(Selection::CONFIG_KEY, 'malformed');
        $this->expectException(LocalizedException::class);
        $observer->execute($event);
    }
}
