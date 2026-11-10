<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\ProductSelection;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Relation;

/** Load direct parents in the feed store, including disabled parents. */
class Parents
{
    /** @var list<string> */
    private array $attributes = ['*'];
    /** @var array<int, list<int>> Relations for a bounded range of 500 child IDs. */
    private array $relations = [];
    /** @var int|null */
    private ?int $window = null;
    /** @var int|null */
    private ?int $storeId = null;
    /** @var array<int, Product|null> Bounded cache including unavailable parents. */
    private array $products = [];

    /**
     * Start a generation with only the attributes its parent predicates need.
     *
     * @param list<string> $attributes
     * @return void
     */
    public function reset(array $attributes): void
    {
        $this->attributes = $attributes;
        $this->relations = [];
        $this->products = [];
        $this->window = null;
        $this->storeId = null;
    }

    /**
     * Constructor.
     *
     * @param Relation $relation
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(private Relation $relation, private CollectionFactory $collectionFactory)
    {
    }

    /**
     * Resolve direct parents assigned to the feed website, with store-view attribute values.
     *
     * @param Product $product
     * @param int $storeId
     * @return iterable<Product>
     */
    public function get(Product $product, int $storeId): iterable
    {
        $connection = $this->relation->getConnection();
        if (!$connection) {
            throw new \Magento\Framework\Exception\LocalizedException(__(
                'Product selection cannot read parent relationships.'
            ));
        }
        $childId = (int)$product->getId();
        $window = intdiv($childId, 500) * 500;
        if ($this->window !== $window) {
            $this->relations = [];
            $rows = $connection->fetchAll($connection->select()
                ->from($this->relation->getMainTable(), ['child_id', 'parent_id'])
                ->where('child_id >= ?', $window)->where('child_id < ?', $window + 500));
            foreach ($rows as $row) {
                $this->relations[(int)$row['child_id']][] = (int)$row['parent_id'];
            }
            $this->window = $window;
        }
        $ids = $this->relations[$childId] ?? [];
        if (!$ids) {
            return [];
        }
        if ($this->storeId !== $storeId || count($this->products) >= 256) {
            $this->products = [];
            $this->storeId = $storeId;
        }
        $missing = array_values(array_filter($ids, fn ($id) => !array_key_exists($id, $this->products)));
        if ($missing) {
            $collection = $this->collectionFactory->create();
            $collection->setFlag('has_stock_status_filter', true);
            $collection->setStoreId($storeId)->addStoreFilter($storeId)
                ->addAttributeToSelect($this->attributes)->addIdFilter($missing);
            foreach ($missing as $id) {
                $this->products[$id] = null;
            }
            foreach ($collection as $parent) {
                $this->products[(int)$parent->getId()] = $parent;
            }
        }
        // Return only this child's direct parents; never combine relationships between children.
        $result = array_values(array_filter(array_intersect_key($this->products, array_flip($ids))));
        if (count($this->products) > 256) {
            $this->products = [];
        }
        return $result;
    }
}
