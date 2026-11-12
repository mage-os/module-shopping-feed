<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Adminhtml;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\Adminhtml\FeedFormData;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Feed\Upload;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class FeedFormDataTest extends TestCase
{
    public function testProviderProjectionNeverSerializesCredentialsOrModelInternals(): void
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()
            ->onlyMethods(['getSchedules', 'getUploads'])->getMock();
        $feed->setData(['id' => 7, 'type' => 'generic', 'name' => 'Example', 'store_id' => 1,
            'internal_token' => 'private', 'config' => new DataObject([
                'columns_product_columns' => [3 => ['column' => 'zero', 'param' => '0']], 'private_config' => 'hidden',
                'promotions_enabled' => 0, 'custom_boolean' => false, 'general_use_default_stock' => '0'
            ])]);
        $feed->method('getSchedules')->willReturn([['id' => 8, 'feed_id' => 7, 'processed_at' => 'private']]);
        $feed->method('getUploads')->willReturn([
            ['id' => 9, 'password' => 'decrypted-secret', 'host' => 'example.invalid', 'internal_secret' => 'hidden',
                'host_key_fingerprint' => 'SHA256:' . str_repeat('A', 43)],
            ['id' => null, 'password' => 'new-secret']
        ]);
        $data = (new FeedFormData())->project($feed, [
            'columns_product_columns', 'promotions_enabled', 'custom_boolean', 'general_use_default_stock'
        ]);
        self::assertSame(0, $data['config']['promotions_enabled']);
        self::assertFalse($data['config']['custom_boolean']);
        self::assertSame('0', $data['config']['general_use_default_stock']);
        self::assertSame(Upload::OBSCURED_VALUE, $data['uploads'][0]['password']);
        self::assertSame('SHA256:' . str_repeat('A', 43), $data['uploads'][0]['host_key_fingerprint']);
        self::assertSame('', $data['uploads'][1]['password']);
        self::assertSame([['id' => 8]], $data['schedules']);
        self::assertSame(
            [['column' => 'zero', 'param' => '0', 'position' => 1]],
            $data['config']['columns_product_columns']
        );
        self::assertStringNotContainsString('secret', json_encode($data));
        self::assertStringNotContainsString('private', json_encode($data));
        self::assertStringNotContainsString('hidden', json_encode($data));
    }

    public function testEmptyArraysAndStructuredCustomParametersSurviveTransport(): void
    {
        $input = ['id' => 7, 'config' => [
            'filters_find_and_replace' => [], 'filters_skip_column_empty' => [],
            'columns_product_columns' => [['column' => 'custom', 'attribute' => 'vendor_directive',
                'param' => ['zero' => '0', 'bool' => false, 'empty' => [], 'literal' => '${ value } <script>']]],
            'custom_key' => ['untouched' => 0], 'general_use_default_stock' => 0,
            'categories_provider_taxonomy_by_category' => ['3' => ['d' => 0, 'p' => 0, 'tx' => '', 'ty' => '']]
        ], 'schedules' => [], 'uploads' => []];
        self::assertSame($input, (new FeedFormData())->decode(json_encode($input)));
    }

    public function testXmlSettingsSurviveProviderAndSaveTransport(): void
    {
        $data = ['config' => [
            'output_params_format' => 'xml',
            'output_params_xml_header' => '<?xml version="1.0"?><rss xmlns:g="urn:google"><channel>',
            'output_params_xml_item' => 'item', 'output_params_xml_footer' => '</channel></rss>',
            'output_params_xml_optional_columns' => 'description,brand',
            'output_params_xml_cdata_columns' => '*',
        ]];
        $form = new FeedFormData();
        self::assertSame($data, $form->decode($form->encodeForProvider($data)));
    }

    public function testProviderEnvelopeSurvivesMagentoTemplateSanitizationWithoutChangingNestedArrays(): void
    {
        $data = ['config' => ['columns_product_columns' => [
            ['param' => ['zero' => 0, 'flag' => false, 'nested' => ['${ literal }', '<b>text</b>']]]
        ]]];
        $json = (new FeedFormData())->encodeForProvider($data);
        $sanitized = (new \Magento\Framework\View\Element\UiComponent\DataProvider\Sanitizer())
            ->sanitize(['data' => $json]);
        self::assertSame(['data' => $json], $sanitized);
        self::assertSame($data, json_decode($sanitized['data'], true));
        self::assertStringNotContainsString('${', $json);
        self::assertStringNotContainsString('<b>', $json);
    }

    public function testLegacyStaticFallbackIsEditableThroughTheCurrentDirectiveParameter(): void
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()
            ->onlyMethods(['getSchedules', 'getUploads'])->getMock();
        $feed->setData('config', new DataObject(['filters_map_replace_empty_columns' => [
            ['column' => 'brand', 'order' => 10, 'static' => 'Old literal']
        ], 'filters_find_and_replace' => '', 'filters_output_limit' => null]));
        $feed->method('getSchedules')->willReturn([]);
        $feed->method('getUploads')->willReturn([]);
        $data = (new FeedFormData())->project($feed, [
            'filters_map_replace_empty_columns', 'filters_find_and_replace', 'filters_output_limit'
        ]);
        $row = $data['config']['filters_map_replace_empty_columns'][0];
        self::assertSame('directive_static_value', $row['attribute']);
        self::assertSame('Old literal', $row['param']);
        self::assertArrayNotHasKey('static', $row);
        self::assertSame([], $data['config']['filters_find_and_replace']);
        self::assertSame([], $data['config']['filters_output_limit']);
    }

    public function testChildDeletionKeepsOwnedIdsForThePersistenceOwnershipCheck(): void
    {
        $data = (new FeedFormData())->decode(json_encode(['config' => [], 'uploads' => [
            ['id' => 4, 'feed_id' => 999, 'delete' => true, 'password' => Upload::OBSCURED_VALUE],
            ['id' => null, 'delete' => true, 'password' => 'discard'],
            ['id' => null, 'delete' => false, 'password' => 'new']
        ]]));
        self::assertCount(2, $data['uploads']);
        self::assertSame(4, $data['uploads'][0]['id']);
        self::assertTrue($data['uploads'][0]['delete']);
        self::assertArrayNotHasKey('feed_id', $data['uploads'][0]);
        self::assertSame('new', $data['uploads'][1]['password']);
        $recovery = (new FeedFormData())->redact($data);
        self::assertSame('', $recovery['uploads'][1]['password']);
        self::assertSame(Upload::OBSCURED_VALUE, $recovery['uploads'][0]['password']);
    }

    public function testMalformedRowsAreRejectedBeforeBuildingTheFeed(): void
    {
        $this->expectException(LocalizedException::class);
        (new FeedFormData())->decode('{"config":{"columns_product_columns":["invalid"]}}');
    }

    public function testRuleOrderFollowsDynamicRowsPositionsWithoutPersistingUiBookkeeping(): void
    {
        $data = (new FeedFormData())->decode(json_encode(['config' => ['filters_find_and_replace' => [
            ['find' => 'second', 'position' => 2, 'record_id' => 0, 'initialize' => true],
            ['find' => 'first', 'position' => 1, 'record_id' => 1]
        ]]]));
        self::assertSame([['find' => 'first'], ['find' => 'second']], $data['config']['filters_find_and_replace']);
    }

    public function testModifierOrderEditsControlTheChainWithoutPersistingUiBookkeeping(): void
    {
        $data = (new FeedFormData())->decode(json_encode(['config' => ['filters_column_modifiers' => [
            ['column' => 'title', 'operation' => 'uppercase', 'position' => 20, 'record_id' => 0],
            ['column' => 'title', 'operation' => 'append', 'value' => 'abc', 'position' => 10, 'record_id' => 1]
        ]]]));
        $rows = $data['config']['filters_column_modifiers'];
        self::assertSame(['append', 'uppercase'], array_column($rows, 'operation'));
        self::assertArrayNotHasKey('position', $rows[0]);
        self::assertArrayNotHasKey('record_id', $rows[0]);
        $modifiers = new \MageOS\ShoppingFeed\Model\Product\ModifierChain();
        self::assertSame('XABC', $modifiers->apply('x', $modifiers->compile($rows)['title'], 'USD'));
    }

    public function testMalformedJsonIsARecoverableValidationError(): void
    {
        $this->expectException(LocalizedException::class);
        (new FeedFormData())->decode('{bad json');
    }
}
