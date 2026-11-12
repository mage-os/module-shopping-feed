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

namespace MageOS\ShoppingFeed\Model\Product;

class Shipping
{
    /**
     * @var Adapter\AdapterAbstract
     */
    protected $adapter;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @var \Magento\Shipping\Model\Shipping
     */
    protected $shipping;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $addressFactory;

    /**
     * @var array
     */
    protected $rates = [];

    /**
     * @var \Magento\Directory\Model\Region
     */
    protected $region;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Helper\Catalog
     */
    protected $catalogHelper;

    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\Cache
     */
    protected $cache;

    /**
     * @var null|array
     */
    protected $shippingTerritory = null;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * Shipping constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Shipping\Model\Shipping $shipping
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Directory\Model\Region $region
     * @param \MageOS\ShoppingFeed\Model\Generator\Cache $cache
     * @param Adapter\AdapterAbstract $adapter
     * @param Helper\Catalog $catalogHelper
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Shipping\Model\Shipping $shipping,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Directory\Model\Region $region,
        \MageOS\ShoppingFeed\Model\Generator\Cache $cache,
        Adapter\AdapterAbstract $adapter,
        \MageOS\ShoppingFeed\Model\Product\Helper\Catalog $catalogHelper,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {

        $this->config = $config;
        $this->adapter = $adapter;
        $this->shipping = $shipping;
        $this->taxHelper = $taxHelper;
        $this->addressFactory = $addressFactory;
        $this->region = $region;
        $this->cache = $cache;
        $this->catalogHelper = $catalogHelper;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * Build a one-item rate request for the product, shipped from the store's shipping origin.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Quote\Model\Quote\Address\RateRequest
     */
    public function setRequest(\Magento\Catalog\Model\Product $product)
    {
        /**
 * cache this
*/
        if ($this->cache->getCache('shipping/origin/country_id', false) === false) {
            $this->cache->setCache(
                'shipping/origin/country_id',
                $this->config->getValue('shipping/origin/country_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            );
            $this->cache->setCache(
                'shipping/origin/region_id',
                $this->config->getValue('shipping/origin/region_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            );
            $this->cache->setCache(
                'shipping/origin/postcode',
                $this->config->getValue('shipping/origin/postcode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            );
        }
        $shippingOriginCountryId = $this->cache->getCache('shipping/origin/country_id');
        $shippingOriginRegionId = $this->cache->getCache('shipping/origin/region_id');
        $shippingOriginPostal = $this->cache->getCache('shipping/origin/postcode');

        $currency = $this->adapter->getStore()->getBaseCurrency();
        $rateRequest = new \Magento\Quote\Model\Quote\Address\RateRequest();
        $rateRequest->setBaseCurrency($currency);
        $rateRequest->setWebsiteId($this->adapter->getStore()->getWebsiteId());
        $rateRequest->setStoreId($this->adapter->getStore()->getStoreId());

        $rateRequest->setOrigCountryId($shippingOriginCountryId)
            ->setOrigRegionId($shippingOriginRegionId)
            ->setOrigPostcode($shippingOriginPostal);

        $price = $product->getPrice();
        $weight = $this->getProductWeight($product);

        $rateRequest->setPackageValue($price)
            ->setPackageWeight($weight)
            ->setFreeMethodWeight($weight)
            ->setPackageCurrency($currency)
            ->setPackageQty(1);

        $rateRequest->setOrderTotalQty(1)
            ->setOrderSubtotal($price);

        $item = $this->quoteItemFactory->create()
            ->setQuote($this->quoteFactory->create())
            ->setProduct($product)
            ->addQty(1)
            ->setAddress($this->addressFactory->create());

        $rateRequest->setAllItems([$item]);

        return $rateRequest;
    }

    /**
     * Collect the allowed carriers' rates for every country/region of the feed's shipping territory.
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $rateRequest
     * @return $this
     */
    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $rateRequest)
    {
        $this->rates = [];
        $allowedCarriers = $this->getAllowedCarriers();

        $ter = $this->getShippingTerritory();
        if (empty($ter)) {
            return $this;
        }

        $onlyMinimumPrice = $this->adapter->getFeed()->getConfig('shipping_only_minimum');
        $onlyFreeShipping = $this->adapter->getFeed()->getConfig('shipping_only_free_shipping');

        if (!empty($allowedCarriers)) {
            foreach ($ter as $countryId => $regions) {
                if (!is_array($regions) || count($regions) == 0) {
                    $regions = [null];
                }

                foreach ($regions as $regionId => $regionCode) {
                    if ($regionCode === null) {
                        $regionId = null;
                        $regionCode = '*';
                    }

                    $rateRequest->setDestCountryId($countryId)
                        ->setDestRegionId($regionId);

                    foreach ($allowedCarriers as $carrierCode) {
                        $this->shipping->collectCarrierRates($carrierCode, $rateRequest);
                    }
                    $result = $this->shipping->getResult();
                    if ($onlyMinimumPrice) {
                        $result->sortRatesByPrice();
                    }
                    $result = $result->asArray();
                    if (empty($result)) {
                        continue;
                    }
                    if ($onlyMinimumPrice && is_array($result)) {
                        reset($result);
                        $result = [key($result) => current($result)];
                    }
                    if ($onlyFreeShipping) {
                        $result = $this->filterFreeShipping($result);
                    }
                    $this->rates[$countryId][$regionCode] = $result;
                }
            }
        }

        return $this;
    }

    /**
     * Keep only the zero-priced methods, dropping carriers left without methods.
     *
     * @param  array $result
     * @return array
     */
    public function filterFreeShipping($result)
    {
        if (is_array($result)) {
            foreach ($result as $carrierCode => $carrier) {
                if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                    foreach ($carrier['methods'] as $methodCode => $method) {
                        if ($method['price'] > 0) {
                            unset($result[$carrierCode]['methods'][$methodCode]);
                        }
                    }
                    if (count($result[$carrierCode]['methods']) == 0) {
                        unset($result[$carrierCode]);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Collected rates as the feed's "country:region:title:price" shipping value.
     *
     * @param bool $structured Preserve field boundaries for XML output
     * @return string|\MageOS\ShoppingFeed\Model\Output\MappedValue
     */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh -- legacy feed logic; refactor deferred to keep output identical
    public function getFormatedValue(bool $structured = false)
    {
        if (!is_array($this->rates) || count($this->rates) == 0) {
            return '';
        }

        $this->rates = $this->minimiseData($this->rates);
        $values = [];
        foreach ($this->rates as $countryId => $regions) {
            if (empty($regions)) {
                continue;
            }

            if (is_array($regions)) {
                foreach ($regions as $regionId => $carriers) {
                    if ($regionId == "*") {
                        if (is_array($carriers)) {
                            foreach ($carriers as $carrier) {
                                if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                    foreach ($carrier['methods'] as $method) {
                                        $price = sprintf(
                                            "%.2F %s",
                                            $this->getPriceTax($method['price'], $countryId),
                                            $this->adapter->getData('store_currency_code')
                                        );
                                        $values[] = ['country' => $countryId, 'region' => '',
                                            'service' => $this->getShippingTitle($carrier, $method), 'price' => $price];
                                    }
                                }
                            }
                        }
                        break; // Only 1 set of carriers for countries without shipping vary by region.
                    } else {
                        if (is_array($carriers)) {
                            foreach ($carriers as $carrier) {
                                if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                    foreach ($carrier['methods'] as $methodCode => $method) {
                                        $price = sprintf(
                                            "%.2F %s",
                                            $this->getPriceTax($method['price'], $countryId),
                                            $this->adapter->getData('store_currency_code')
                                        );
                                        $values[] = ['country' => $countryId, 'region' => (string)$regionId,
                                            'service' => $this->getShippingTitle($carrier, $method), 'price' => $price];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $text = implode(',', array_map(static fn (array $group): string => implode(':', $group), $values));
        return $structured && $values !== []
            ? new \MageOS\ShoppingFeed\Model\Output\MappedValue($values, $text) : $text;
    }

    /**
     * Get title slot from shipping format ::{title}:
     *
     * @param array<mixed> $carrier
     * @param array<mixed> $method
     * @return string
     */
    protected function getShippingTitle($carrier, $method)
    {
        $title = '';

        if ($method['title'] != "*" && (!empty($carrier['title']) || !empty($method['title']))) {
            if (!empty($carrier['title'])) {
                $title .= $carrier['title'];
            }
            if (!empty($method['title'])) {
                $title .= empty($title) ? $method['title'] : ' - ' . $method['title'];
            }
        }
        return $title;
    }

    /**
     * Shipping price with the store's shipping tax applied when configured, in the store currency.
     *
     * @param float $price
     * @param string|null $countryId
     * @param int|string|null $regionId
     * @return float
     */
    protected function getPriceTax($price, $countryId = null, $regionId = null)
    {
        $ret = $price;

        // Apply if there is a tax for shipping
        if ($this->taxHelper->getShippingTaxClass($this->adapter->getStore()->getStoreId())) {
            $includingTax = ($this->adapter->getFeed()->getConfig('shipping_add_tax_to_price') ? true : false);

            // Mock quote object, needed only for getBillingAddress()
            $quote = new \Magento\Framework\DataObject();
            /**
 * @var \Magento\Customer\Model\Address $address
*/
            $address = $this->addressFactory->create();

            if ($countryId !== null) {
                $address->setCountryId($countryId);
                if ($regionId !== null && $regionId != "*") {
                    $address->setRegionId($regionId);
                }
            }

            $billingAddress = clone $address;
            $shippingAddress = clone $address;
            $quote->setData('billing_address', $billingAddress);
            $shippingAddress->setQuote($quote);

            $ret = $this->getShippingTaxPrice(
                $price,
                $includingTax,
                $shippingAddress,
                null,
                $this->adapter->getStore()->getStoreId()
            );
        }
        $ret = $this->adapter->convertPrice($ret);

        return $ret;
    }

    /**
     * Transform rates from:
     *
     *    US:CA:x:99 and US:NY:x:99 to US:*:x:99
     *
     * @param  array $rates
     * @return array
     */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh -- legacy feed logic; refactor deferred to keep output identical
    public function minimiseData($rates)
    {
        $ret = $rates;
        if (empty($ret)) {
            return $ret;
        }

        /* Compress by regions
           US:CA:x:99 and US:NY:x:99 to US::x:99 */
        foreach ($rates as $countryId => $regions) {
            if (empty($regions)) {
                continue;
            }

            // Find all methods in all regions
            $allMethods = [];
            foreach ($regions as $regionId => $carriers) {
                if ($regionId != "*") {
                    if (is_array($carriers)) {
                        foreach ($carriers as $carrierCode => $carrier) {
                            if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                foreach ($carrier['methods'] as $methodCode => $method) {
                                    $code = $carrierCode . '~' . $methodCode;
                                    if (!isset($allMethods[$code])) {
                                        $allMethods[$code] = $code;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Find which methods have same prices in all regions ($same) and transform $ret:
            // for each method in all regions with same price => set method in region_id == *.
            $same = [];
            foreach ($allMethods as $code => $v) {
                $same[$code] = PHP_INT_MAX;
            }

            foreach ($regions as $regionId => $carriers) {
                if ($regionId != "*") {
                    $allRegionMethods = [];
                    if (is_array($carriers)) {
                        foreach ($carriers as $carrierCode => $carrier) {
                            if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                foreach ($carrier['methods'] as $methodCode => $method) {
                                    $code = $carrierCode . '~' . $methodCode;
                                    $allRegionMethods[$code] = $code;
                                    if ($same[$code] == PHP_INT_MAX) {
                                        $same[$code] = $method['price'];
                                    } elseif ($same[$code] !== false && $same[$code] != $method['price']) {
                                        $same[$code] = false;
                                    }
                                }
                            }
                        }
                    }

                    $missing = array_diff($allMethods, $allRegionMethods);
                    foreach ($missing as $code => $v) {
                        $same[$code] = false;
                    }
                }
            }

            foreach ($same as $code => $v) {
                if ($same[$code] === false) {
                    unset($same[$code]);
                }
            }

            // Move every redundant method to *
            if (count($same) > 0) {
                foreach ($regions as $regionId => $carriers) {
                    if ($regionId != "*") {
                        if (is_array($carriers)) {
                            foreach ($carriers as $carrierCode => $carrier) {
                                if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                    foreach ($carrier['methods'] as $methodCode => $method) {
                                        $code = $carrierCode . '~' . $methodCode;
                                        if (isset($same[$code])) {
                                            unset($ret[$countryId][$regionId][$carrierCode]['methods'][$methodCode]);

                                            // move once to *
                                            if (!isset($ret[$countryId]["*"])) {
                                                $ret[$countryId]["*"] = [];
                                            }
                                            if (!isset($ret[$countryId]["*"][$carrierCode])) {
                                                $ret[$countryId]["*"][$carrierCode] = [
                                                    'title' => $carrier['title'],
                                                    'methods' => []
                                                ];
                                            }
                                            if (!isset($ret[$countryId]["*"][$carrierCode]['methods'][$methodCode])) {
                                                $ret[$countryId]["*"][$carrierCode]['methods'][$methodCode] = $method;
                                            }
                                        }
                                    }

                                    if (empty($ret[$countryId][$regionId][$carrierCode]['methods'])) {
                                        unset($ret[$countryId][$regionId][$carrierCode]);
                                    }
                                }
                            }
                        }
                    }

                    if (empty($ret[$countryId][$regionId])) {
                        unset($ret[$countryId][$regionId]);
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Carrier codes of the feed's shipping methods, excluding realtime carriers.
     *
     * @return array
     */
    public function getAllowedCarriers()
    {
        $realtimeCarriers = (array) $this->adapter->getFeed()->getConfig('shipping_carrier_realtime', []);
        $methods = $this->adapter->getFeed()->getConfig('shipping_methods');
        $allowedCarriers = [];
        if (!is_array($methods)) {
            return $allowedCarriers;
        }
        foreach ($methods as $m) {
            $separator = is_string($m) ? strpos($m, '_') : false;
            if ($separator !== false && $separator > 0) {
                $allowedCarriers[] = substr($m, 0, $separator);
            }
        }
        $allowedCarriers = array_unique($allowedCarriers);
        $allowedCarriers = array_diff($allowedCarriers, $realtimeCarriers);

        return $allowedCarriers;
    }

    /**
     * Feed shipping countries, each with its regions when shipping varies by region.
     *
     * @return array|null
     */
    public function getShippingTerritory()
    {
        if ($this->shippingTerritory === null) {
            $this->shippingTerritory = [];
            $allowedCountries = $this->adapter->getFeed()->getConfig('shipping_country');
            if (empty($allowedCountries)) {
                return $this->shippingTerritory;
            }
            $shippingByRegion = $this->adapter->getFeed()->getConfig('shipping_by_region');
            $allowedCountryWithRegions = [];
            if ($shippingByRegion) {
                $allowedCountryWithRegions = $this->adapter->getFeed()->getConfig('shipping_country_with_region');
                $allowedCountryWithRegions = empty($allowedCountryWithRegions)
                    ? $allowedCountries
                    : array_intersect($allowedCountryWithRegions, $allowedCountries);
            }

            foreach ($allowedCountries as $countryId) {
                $this->shippingTerritory[$countryId] = [];
            }

            if (count($allowedCountryWithRegions) > 0) {

                /**
 * @var \Magento\Directory\Model\ResourceModel\Region\Collection $regionsCollection
*/
                $regionsCollection = $this->region->getCollection();
                $regionsCollection->addCountryFilter($allowedCountryWithRegions);

                $countryRegions = [];
                foreach ($regionsCollection as $region) {
                    /**
 * @var \Magento\Directory\Model\Region $region
*/
                    $countryRegions[$region->getCountryId()][$region->getId()] = $region->getCode();
                }
                unset($regionsCollection);

                // This list is only filled when shipping varies by region; $countryRegions keys have regions.
                foreach ($allowedCountryWithRegions as $countryId) {
                    $this->shippingTerritory[$countryId] = [];
                    if (isset($countryRegions[$countryId])) {
                        $this->shippingTerritory[$countryId] = $countryRegions[$countryId];
                    }
                }
            }
        }
        return $this->shippingTerritory;
    }

    /**
     * Get shipping price
     * This method copies method getShippingPrice from \Magento\Tax\Helper\Data,
     * but uses another helper to push price calculation
     *
     * @param  float                                           $price
     * @param  bool|null                                       $includingTax
     * @param  \Magento\Customer\Model\Address|null            $shippingAddress
     * @param  int|null                                        $ctc
     * @param  null|string|bool|int|\Magento\Store\Model\Store $store
     * @return float
     */
    protected function getShippingTaxPrice(
        $price,
        $includingTax = null,
        $shippingAddress = null,
        $ctc = null,
        $store = null
    ) {

        $pseudoProduct = new \Magento\Framework\DataObject();
        $pseudoProduct->setTaxClassId($this->taxHelper->getShippingTaxClass($store));

        $billingAddress = false;
        if ($shippingAddress && $shippingAddress->getQuote() && $shippingAddress->getQuote()->getBillingAddress()) {
            $billingAddress = $shippingAddress->getQuote()->getBillingAddress();
        }

        $price = $this->catalogHelper->getTaxPrice(
            $pseudoProduct,
            $price,
            $includingTax,
            $shippingAddress,
            $billingAddress,
            $ctc,
            $store,
            $this->taxHelper->shippingPriceIncludesTax($store)
        );

        return $price;
    }

    /**
     * Compute product weight based on mappings
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return int
     */
    public function getProductWeight(\Magento\Catalog\Model\Product $product)
    {
        $cacheKey = ['shipping', 'map', 'weight', 'product', $product->getId()];
        if (($weight = $this->cache->getCache($cacheKey, false)) !== false) {
            return $weight;
        }

        $weight = $product->getWeight();

        $shippingColumn = $this->adapter->getFeed()->getConfig('shipping_weight_column', '');
        if (!empty($shippingColumn)) {
            $columnsMap = $this->adapter->getFeed()->getColumnsMap();
            $shippingColumnMap = null;
            foreach ($columnsMap as $map) {
                if ($map['column'] == $shippingColumn) {
                    $shippingColumnMap = $map;
                    break;
                }
            }
            if ($shippingColumnMap !== null && isset($shippingColumnMap['param'])) {
                $unit = $shippingColumnMap['param'];

                try {
                    $weight = $this->adapter->getMapValue($shippingColumnMap);
                } catch (\Exception $e) {
                    $weight = 1;
                }
                if ($unit != '') {
                    $weight = trim(str_replace($unit, '', $weight));
                }
            } else {
                $weight = 1;
            }
        }
        $weight = (int) $weight;

        $this->cache->setCache($cacheKey, $weight);
        return $weight;
    }
}
