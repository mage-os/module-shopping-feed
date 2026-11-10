<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple;

use MageOS\ShoppingFeed\Model\ConditionalValue\Matcher;
use MageOS\ShoppingFeed\Model\ConditionalValue\Configuration;
use MageOS\ShoppingFeed\Model\Logger;
use MageOS\ShoppingFeed\Model\Product\Mapper\MapperAbstract;
use Magento\Framework\Exception\LocalizedException;

/** Resolve an if/else value, then apply the destination column's normal text filters once. */
class ConditionalValue extends MapperAbstract
{
    /** @var array<string, array<string, mixed>> */
    private array $validated = [];

    /**
     * Constructor.
     *
     * @param Logger $logger
     * @param Configuration $configurationValidator
     * @param Matcher $matcher
     */
    public function __construct(
        Logger $logger,
        private Configuration $configurationValidator,
        private Matcher $matcher
    ) {
        parent::__construct($logger);
    }

    /**
     * Map the current product without changing its data or recursively mapping feed columns.
     *
     * @param array<string,mixed> $params
     * @return string
     * @throws LocalizedException
     */
    public function map(array $params = [])
    {
        $product = $this->getAdapter()->getProduct();
        if ($product === null) {
            throw new LocalizedException(__('Conditional Value requires a product.'));
        }
        $parameter = $params['param'] ?? null;
        $key = json_encode($parameter, JSON_THROW_ON_ERROR);
        if (!isset($this->validated[$key])) {
            // Bound long-running consumers that encounter many feed configurations.
            if (count($this->validated) >= 100) {
                $this->validated = [];
            }
            $this->validated[$key] = $this->configurationValidator->validate($parameter);
        }
        $configuration = $this->validated[$key];
        $matches = $this->matcher->matches(
            $configuration,
            $product,
            $this->getAdapter()->getParentAdapter()?->getProduct()
        );
        $output = $configuration[$matches ? 'if_true' : 'if_false'];
        return $this->getAdapter()->getFilter()->cleanField($this->output($output), $params);
    }

    /**
     * Read labels for attribute outputs and raw numbers for percentage adjustments.
     *
     * @param array<string,mixed> $output
     * @return string
     * @throws LocalizedException
     */
    private function output(array $output): string
    {
        if ($output['type'] === 'static') {
            return (string)$output['value'];
        }
        $adapter = $this->getAdapter();
        $product = $adapter->getProduct();
        $attribute = $adapter->getMapAttribute(['attribute' => $output['attribute']]);
        if ($product === null || $attribute === false) {
            throw new LocalizedException(__('Conditional Value requires a valid product attribute.'));
        }
        if ($output['type'] === 'attribute') {
            return $adapter->getAttributeValue($product, $attribute);
        }
        $value = $product->getData($output['attribute']);
        if ($value === null || $value === '') {
            return '';
        }
        if (!is_numeric($value) || !is_finite((float)$value)) {
            throw new LocalizedException(
                __('Conditional Value percentage source %1 must be numeric.', $output['attribute'])
            );
        }
        $adjusted = (float)$value * (1 + (float)$output['percent'] / 100);
        if (!is_finite($adjusted)) {
            throw new LocalizedException(__('Conditional Value percentage result must be finite.'));
        }
        return number_format($adjusted, 2, '.', '');
    }
}
