<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Ui\DataProvider\Feed\Form;

use MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Metadata;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class MetadataTest extends TestCase
{
    public function testAllFourPromotionDatesUseTheCanonicalDateComponent(): void
    {
        $fields = $this->createMock(\MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Fields::class);
        $fields->method('get')->willReturn(['general' => ['name' => [],
            'general_stock_attribute_code' => [], 'output_params_delimiter_other' => [],
            'output_params_xml_header' => ['formElement' => 'textarea'],
            'output_params_xml_item' => [], 'output_params_xml_footer' => ['formElement' => 'textarea'],
            'output_params_xml_optional_columns' => [], 'output_params_xml_cdata_columns' => []]]);
        $options = $this->createMock(\MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Options::class);
        $options->method('get')->willReturn([]);
        $parameters = $this->createMock(\MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Parameters::class);
        $parameters->method('get')->willReturn([]);
        $stores = $this->createMock(\Magento\Store\Model\System\Store::class);
        $stores->method('getStoreValuesForForm')->willReturn([]);
        $authorization = $this->createMock(\Magento\Framework\AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(true);
        $categories = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Category\CollectionProvider::class);
        $categories->method('getCategories')->willReturn([]);
        $taxonomy = $this->createMock(\MageOS\ShoppingFeed\Model\Taxonomy\ProviderFactory::class);
        $promotions = $this->createMock(\MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Promotions::class);
        $promotions->method('rules')->willReturn([new \Magento\Framework\DataObject(['id' => 12, 'name' => 'Sale'])]);
        $metadata = new Metadata(
            $fields,
            $options,
            $parameters,
            $stores,
            $authorization,
            $categories,
            $taxonomy,
            $promotions
        );
        $feed = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Feed::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData('type', 'google_shopping');
        $meta = $metadata->get($feed);
        $xmlHeader = $meta['general']['children']['output_params_xml_header']['arguments']['data']['config'];
        self::assertSame('textarea', $xmlHeader['formElement']);
        self::assertSame('MageOS_ShoppingFeed/js/form/dependent-input', $xmlHeader['component']);
        self::assertSame('xml', $xmlHeader['showWhen']);
        $children = $meta['promotions']['children']['rule_12']['children'];
        foreach (['date_from', 'date_to', 'display_from', 'display_to'] as $key) {
            $config = $children[$key]['arguments']['data']['config'];
            self::assertSame('MageOS_ShoppingFeed/js/form/promotion-date', $config['component']);
            self::assertSame('date', $config['formElement']);
        }
    }
}
