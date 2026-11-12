<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Ui\DataProvider\Feed\Form;

use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Feed\Source\Product\DirectivesAndAttributes;
use MageOS\ShoppingFeed\Model\FeedTypes\Config;
use MageOS\ShoppingFeed\Model\FeedTypes\Config\Converter;
use MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Metadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class MappingDefaultsTest extends TestCase
{
    private static function definition(): \DOMDocument
    {
        $document = new \DOMDocument();
        $document->load(dirname(__DIR__, 6) . '/etc/mageos_shopping_feed.xml');
        return $document;
    }

    /** @return array<string,array{string,string,string}> */
    public static function mappingSections(): array
    {
        $cases = [];
        $feeds = (new \DOMXPath(self::definition()))->query('/config/feed');
        if ($feeds === false) {
            throw new \RuntimeException('Unable to query feed definitions.');
        }
        foreach ($feeds as $feed) {
            if (!$feed instanceof \DOMElement) {
                throw new \RuntimeException('Expected a feed element.');
            }
            $sections = ['columns' => 'columns_product_columns', 'filters' => 'filters_map_replace_empty_columns'];
            foreach ($sections as $section => $key) {
                $type = $feed->getAttribute('name');
                $cases[$type . '/' . $section] = [$type, $section, $key];
            }
        }
        return $cases;
    }

    /** @dataProvider mappingSections */
    #[DataProvider('mappingSections')]
    public function testNewMappingsDefaultToStaticValue(string $type, string $section, string $key): void
    {
        $decoder = $this->createMock(\Magento\Framework\Json\DecoderInterface::class);
        $decoder->method('decode')->willReturnCallback(static fn($value) => json_decode($value, true));
        $types = (new Converter($decoder))->convert(self::definition())['feed'];
        $config = $this->createMock(Config::class);
        $config->method('getDirectives')->with($type)->willReturn($types[$type]['directives']);
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()
            ->onlyMethods(['getConfig', 'isTaxonomyAutocompleteEnabled'])->getMock();
        $feed->setData('type', $type);
        $feed->method('getConfig')->willReturn([]);
        $feed->method('isTaxonomyAutocompleteEnabled')->willReturn(false);
        $registry = new \Magento\Framework\Registry();
        $registry->register('feed', $feed);
        $attributes = $this->createMock(\MageOS\ShoppingFeed\Model\Feed\Source\Product\Attributes::class);
        $attributes->method('getAttributes')->willReturn([]);
        $source = new DirectivesAndAttributes(
            $registry,
            $this->createMock(\Magento\Eav\Model\Config::class),
            $config,
            $attributes
        );
        $options = $this->createMock(\MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Options::class);
        $options->method('get')->willReturnCallback(
            static fn($name) => $name === 'DirectivesAndAttributes' ? $source->toOptionArray() : []
        );
        $fields = $this->createMock(\MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Fields::class);
        $fields->method('get')->willReturn(['general' => ['name' => [],
            'general_stock_attribute_code' => [], 'output_params_delimiter_other' => []]]);
        $parameters = $this->createMock(\MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Parameters::class);
        $parameters->method('get')->willReturn([]);
        $stores = $this->createMock(\Magento\Store\Model\System\Store::class);
        $stores->method('getStoreValuesForForm')->willReturn([]);
        $authorization = $this->createMock(\Magento\Framework\AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(true);
        $categories = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Category\CollectionProvider::class);
        $categories->method('getCategories')->willReturn([]);
        $promotions = $this->createMock(\MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form\Promotions::class);
        $promotions->method('rules')->willReturn([]);
        $metadata = new Metadata(
            $fields,
            $options,
            $parameters,
            $stores,
            $authorization,
            $categories,
            $this->createMock(\MageOS\ShoppingFeed\Model\Taxonomy\ProviderFactory::class),
            $promotions
        );
        $field = $metadata->get($feed)[$section]['children'][$key]['children']['record']
            ['children']['attribute']['arguments']['data']['config'];
        $directives = array_column($field['options'][0]['value'], 'value');
        // Magento selects the first option when a new row has no saved value or explicit default.
        self::assertSame('directive_static_value', $field['default'] ?? $directives[0]);
        self::assertContains('directive_conditional_value', $directives);
    }
}
