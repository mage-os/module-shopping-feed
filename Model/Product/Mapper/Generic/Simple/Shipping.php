<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_ShoppingFeed
 * @copyright Copyright (c) 2016 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */

namespace MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple;

use MageOS\ShoppingFeed\Model\Generator\Cache;
use \MageOS\ShoppingFeed\Model\Product\Mapper\MapperAbstract;

/**
 * Fetches shipping values from:
 * 1. Cache Class
 * 2. Cache/Shipping DB table
 * 3. Calculates the values and saves them in DB & Class cache
 *
 * Class Shipping
 *
 */
class Shipping extends MapperAbstract
{
    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\Cache\ShippingProvider
     */
    protected $shippingProvider;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Shipping mapper constructor.
     *
     * @param Cache $cache
     * @param \MageOS\ShoppingFeed\Model\Generator\Cache\ShippingProvider $shippingProvider
     * @param \MageOS\ShoppingFeed\Model\Logger $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Cache $cache,
        \MageOS\ShoppingFeed\Model\Generator\Cache\ShippingProvider $shippingProvider,
        \MageOS\ShoppingFeed\Model\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {

        $this->shippingProvider = $shippingProvider;
        $this->cache = $cache;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($logger);
    }

    /**
     * Shipping value from the class cache, the persistent shipping cache, or freshly computed rates.
     *
     * @param  array $params
     * @return string|\MageOS\ShoppingFeed\Model\Output\MappedValue
     */
    public function map(array $params = [])
    {
        $allowedCountries = $this->getAdapter()->getFeed()->getConfig('shipping_country');
        if (!is_array($allowedCountries)) {
            return '';
        }

        $allowedCountries = array_filter($allowedCountries);
        if (count($allowedCountries) == 0) {
            return '';
        }

        $xml = $this->getAdapter()->getFeed()->getConfig('output_params_format', 'delimited') === 'xml';
        // Separate formats so switching a feed cannot reuse a flattened XML value.
        $format = $xml ? 'xml-v1' : 'delimited';
        $cacheKey = ['shipping', 'map', 'product', 'rate', $this->getAdapter()->getProduct()->getId(), $format];
        $value = $this->cache->getCache($cacheKey, false);
        if ($value !== false && $value !== null) {
            return $value;
        }

        // product persistent cache
        $scheduledCurrencyUpdateEnabled = $this->scopeConfig->getValue(
            \Magento\Directory\Model\Observer::IMPORT_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $useShippingCache = $this->getAdapter()->getFeed()->getConfig('shipping_cache_enabled')
            && !$scheduledCurrencyUpdateEnabled;
        $cacheTtl = $useShippingCache ? $this->getAdapter()->getFeed()->getConfig('shipping_ttl') : 0;

        $this->shippingProvider->prepareCache($this->getAdapter(), $this->getAdapter()->getProduct(), $cacheTtl);
        $stored = $this->shippingProvider->getCache();
        $value = $stored;
        // Old flattened entries are recomputed for XML. The marker is impossible in a country code.
        $marker = 'xml-shipping-v1:';
        $encoded = is_string($stored) && str_starts_with($stored, $marker);
        if ($encoded && $xml) {
            $data = json_decode(substr($stored, strlen($marker)), true);
            $value = is_array($data) && isset($data['values'], $data['text'])
                ? new \MageOS\ShoppingFeed\Model\Output\MappedValue($data['values'], $data['text']) : false;
        } elseif ($xml || $encoded) {
            $value = false;
        }
        if ($value !== false) {
            $this->shippingProvider->setCache($stored);
            return $value;
        }

        try {
            $shipping = $this->shippingProvider->getShipping($this->getAdapter());

            // price_weight cache
            $weight = $shipping->getProductWeight($this->getAdapter()->getProduct());
            $price = $this->getAdapter()->getProduct()->getPrice();
            $secondCacheKey = ['shipping', 'map', 'price_weight', 'rate', $weight, $price, $format];
            if (($value = $this->cache->getCache($secondCacheKey, false)) === false) {
                $request = $shipping->setRequest($this->getAdapter()->getProduct());
                $shipping->collectRates($request);
                $value = $shipping->getFormatedValue($xml);
            }

            $this->cache->setCache($secondCacheKey, $value);
            $stored = $value instanceof \MageOS\ShoppingFeed\Model\Output\MappedValue
                ? $marker . json_encode(['values' => $value->values, 'text' => (string)$value], JSON_THROW_ON_ERROR)
                : $value;
            $this->shippingProvider->setCache($stored);
            $this->cache->setCache($cacheKey, $value);
            return $value;
        } catch (\Exception $e) {
            $this->getAdapter()->getLogger()->error(
                sprintf(
                    'ERROR when computing shipping value for product id %s: %s',
                    $this->getAdapter()->getProduct()->getId(),
                    $e->getMessage()
                )
            );
            return '';
        }
    }
}
