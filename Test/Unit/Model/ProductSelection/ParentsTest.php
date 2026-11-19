<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\ProductSelection;

use MageOS\ShoppingFeed\Model\ProductSelection\Parents;
use MageOS\ShoppingFeed\Test\Unit\CompatibilityTestCase;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Relation;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ParentsTest extends CompatibilityTestCase
{
    public function testSharedParentLoadsOnceButStoreAndGenerationChangesReloadIt(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->willReturn([10]);
        $connection->method('fetchAll')->willReturn([
            ['child_id' => 7, 'parent_id' => 10], ['child_id' => 8, 'parent_id' => 10]
        ]);
        $relation = $this->createMock(Relation::class);
        $relation->method('getConnection')->willReturn($connection);
        $relation->method('getMainTable')->willReturn('catalog_product_relation');
        $parent = $this->getMockBuilder(Product::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $parent->setData(['entity_id' => 10, 'sku' => 'parent']);
        $collection = $this->createMock(Collection::class);
        foreach (['setStoreId', 'addStoreFilter', 'addAttributeToSelect', 'addIdFilter'] as $method) {
            $collection->method($method)->willReturnSelf();
        }
        $collection->method('getIterator')->willReturnCallback(static fn () => new \ArrayIterator([$parent]));
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects(self::exactly(3))->method('create')->willReturn($collection);
        $parents = new Parents($relation, $factory);
        $child = $this->getMockBuilder(Product::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $child->setData('entity_id', 7);
        self::assertSame([$parent], [...$parents->get($child, 1)]);
        $child->setData('entity_id', 8);
        self::assertSame([$parent], [...$parents->get($child, 1)]);
        self::assertSame([$parent], [...$parents->get($child, 2)]);
        $parents->reset(['sku']);
        self::assertSame([$parent], [...$parents->get($child, 2)]);
    }
}
