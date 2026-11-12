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
        $canSave = true;
        $authorization->method('isAllowed')->willReturnCallback(static function () use (&$canSave): bool {
            return $canSave;
        });
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
        $modifiers = $meta['columns']['children']['filters_column_modifiers'];
        self::assertTrue($modifiers['arguments']['data']['config']['dndConfig']['enabled']);
        $record = $modifiers['children']['record']['children'];
        self::assertTrue($record['position']['arguments']['data']['config']['visible'] ?? true);
        self::assertSame('Order', (string)$record['position']['arguments']['data']['config']['label']);
        self::assertSame('column', $record['column']['arguments']['data']['config']['dataScope']);
        self::assertTrue($record['column']['arguments']['data']['config']['validation']['required-entry']);
        self::assertSame(\MageOS\ShoppingFeed\Model\Product\ModifierChain::OPERATIONS, array_column(
            $record['operation']['arguments']['data']['config']['options'],
            'value'
        ));
        self::assertContains('filters_column_modifiers', $metadata->configKeys($feed));
        $canSave = false;
        $readOnly = $metadata->get($feed)['columns']['children']['filters_column_modifiers'];
        self::assertFalse($readOnly['arguments']['data']['config']['addButton']);
        self::assertFalse($readOnly['arguments']['data']['config']['dndConfig']['enabled']);
        self::assertArrayNotHasKey('action_delete', $readOnly['children']['record']['children']);
        $readOnlyValue = $readOnly['children']['record']['children']['value']['arguments']['data']['config'];
        self::assertTrue($readOnlyValue['disabled']);

        $xmlHeader = $meta['general']['children']['output_params_xml_header']['arguments']['data']['config'];
        self::assertSame('textarea', $xmlHeader['formElement']);
        self::assertSame('MageOS_ShoppingFeed/js/form/dependent-textarea', $xmlHeader['component']);
        self::assertSame('xml', $xmlHeader['showWhen']);
        $children = $meta['promotions']['children']['rule_12']['children'];
        foreach (['date_from', 'date_to', 'display_from', 'display_to'] as $key) {
            $config = $children[$key]['arguments']['data']['config'];
            self::assertSame('MageOS_ShoppingFeed/js/form/promotion-date', $config['component']);
            self::assertSame('date', $config['formElement']);
        }
    }
}
