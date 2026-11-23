<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model;

use MageOS\ShoppingFeed\Model\Output\Xml;
use PHPUnit\Framework\TestCase;

class XmlOutputTest extends TestCase
{
    public function testRoundTripEscapingRepeatedTagsAndOptionalValues(): void
    {
        $writer = new Xml('<products>', 'product', '</products>', ['empty'], ['description']);
        $value = 'café 東京 & < > " ]]> tail';
        $xml = $writer->header() . $writer->row([
            'id' => '0', 'description' => $value, 'url' => ['a&b', 'c<d'], 'empty' => '', 'required' => ''
        ]) . $writer->footer();
        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($xml));
        self::assertSame($value, $document->getElementsByTagName('description')->item(0)?->textContent);
        self::assertSame('0', $document->getElementsByTagName('id')->item(0)?->textContent);
        self::assertSame(2, $document->getElementsByTagName('url')->length);
        self::assertSame(0, $document->getElementsByTagName('empty')->length);
        self::assertSame(1, $document->getElementsByTagName('required')->length);
        self::assertStringContainsString('<![CDATA[', $xml);
    }

    public function testNamespacesAndEmptyDocument(): void
    {
        $writer = new Xml('<rss xmlns:g="urn:google"><channel>', 'item', '</channel></rss>');
        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($writer->header() . $writer->footer()));
        self::assertTrue($document->loadXML($writer->header() . $writer->row(['g:id' => '1']) . $writer->footer()));
        self::assertSame('1', $document->getElementsByTagNameNS('urn:google', 'id')->item(0)?->textContent);
    }

    public function testUnicodeElementNames(): void
    {
        $writer = new Xml('<éléments>', 'élément', '</éléments>');
        self::assertStringContainsString('<größe>0</größe>', $writer->row(['größe' => 0]));
    }

    public function testInvalidTextIsDistinguishedFromInvalidConfiguration(): void
    {
        $writer = new Xml('<products>', 'product', '</products>');
        $this->expectException(\MageOS\ShoppingFeed\Model\Output\InvalidValueException::class);
        $writer->row(['description' => "bad\x0Btext"]);
    }

    /** @dataProvider invalidColumns */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidColumns')]
    public function testValidateColumnsRejectsInvalidOrUndeclaredNames(string $name): void
    {
        $writer = new Xml('<products>', 'product', '</products>', ['*']);
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $writer->validateColumns([$name]);
    }

    /** @return list<array{string}> */
    public static function invalidColumns(): array
    {
        return [['Product Name'], ['price (USD)'], ['g:custom'], ['a:b:c'], ['<injection>']];
    }

    public function testRejectsDtdBeforeParsing(): void
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        new Xml('<!DOCTYPE root SYSTEM "file:///etc/passwd"><root>', 'item', '</root>');
    }

    public function testRejectsMismatchedWrappers(): void
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        new Xml('<products>', 'product', '</wrong>');
    }

    public function testRejectsWrappersThatHideItemsInComments(): void
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        new Xml('<products><!--', 'product', '--></products>');
    }

    public function testRejectsUndeclaredNamespace(): void
    {
        $writer = new Xml('<products>', 'product', '</products>');
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $writer->row(['g:id' => '1']);
    }

    public function testRejectsInvalidXmlCharacters(): void
    {
        $writer = new Xml('<products>', 'product', '</products>');
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $writer->row(['id' => "bad\x00value"]);
    }
}
