<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model;

use MageOS\ShoppingFeed\Model\Feed;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class XmlConfigurationTest extends TestCase
{
    /**
     * @param array<string,mixed> $settings
     * @dataProvider settings
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('settings')]
    public function testSaveValidation(string $type, array $settings, bool $valid): void
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['type' => $type, 'config' => new DataObject($settings + [
            'output_params_format' => 'xml', 'columns_product_columns' => [['column' => 'größe']]
        ])]);
        (new \ReflectionProperty($feed, 'outputPath'))->setValue(
            $feed,
            $this->createMock(\MageOS\ShoppingFeed\Model\Feed\OutputPath::class)
        );
        if (!$valid) {
            $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        }
        self::assertSame($feed, (new \ReflectionMethod($feed, 'validate'))->invoke($feed));
    }

    /** @return array<array{string,array<string,mixed>,bool}> */
    public static function settings(): array
    {
        return [
            ['generic', [], true],
            ['generic', ['output_params_xml_header' => "<catalog\n version=\"1\">",
                'output_params_xml_footer' => '</catalog>', 'output_params_xml_item' => 'élément'], true],
            ['generic', ['columns_product_columns' => [['column' => 'Product Name']]], false],
            ['generic', ['columns_product_columns' => [['column' => 'g:id']],
                'output_params_xml_optional_columns' => '*'], false],
            ['generic', ['output_params_xml_header' => ' <?xml version="1.0"?><products>'], false],
            ['generic', ['output_params_xml_footer' => '</wrong>'], false],
            ['generic', ['output_params_xml_header' =>
                '<!DOCTYPE products SYSTEM "file:///etc/passwd"><products>'], false],
            ['openai_google_compatible', [], false],
            ['generic', ['output_params_format' => 'delimited',
                'columns_product_columns' => [['column' => 'Product Name']]], true],
        ];
    }
}
