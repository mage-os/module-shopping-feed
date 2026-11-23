<?php
declare(strict_types=1);
namespace MageOS\ShoppingFeed\Test\Unit\Block\Adminhtml\Feed\Test;

use MageOS\ShoppingFeed\Block\Adminhtml\Feed\Test\Result;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ResultTest extends TestCase
{
    public function testProductCellsAreEscapedWithoutTranslation(): void
    {
        $renderer = Phrase::getRenderer();
        $translator = $this->createMock(\Magento\Framework\Phrase\RendererInterface::class);
        $translator->method('render')
            ->willReturnCallback(static fn($source, $arguments) => 'translated ' . end($source));
        Phrase::setRenderer($translator);
        $block = new class {
            /**
             * @return array<mixed>
             */
            public function getMessages()
            {
                return [];
            }
            /**
             * @return array<int, array<int, array<string, string>>>
             */
            public function getTestOutput()
            {
                return [[['label' => 'custom_%1', 'value' => 'Product %1 <tag>']]];
            }
            /**
             * @return string
             */
            public function getLogOutput()
            {
                return '';
            }
            /**
             * @param mixed $value
             * @return string
             */
            public function escapeHtml($value)
            {
                return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            }
        };
        // Magento's template engine also provides $escaper to every template.
        $escaper = $block;
        ob_start();
        try {
            include dirname(__DIR__, 6) . '/view/adminhtml/templates/feed/test/result.phtml';
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
            Phrase::setRenderer($renderer);
        }
        $this->assertStringContainsString('>Product %1 &lt;tag&gt;</td>', $html);
        $this->assertStringContainsString('>custom_%1</th>', $html);
        $this->assertStringContainsString('translated Column', $html);
    }

    public function testGenerationErrorsAreLoggedWithoutExposingTheTrace(): void
    {
        $block = (new \ReflectionClass(Result::class))->newInstanceWithoutConstructor();
        $registry = $this->createMock(\Magento\Framework\Registry::class);
        $registry->method('registry')->willReturn(new \Magento\Framework\DataObject(['id' => 1, 'sku' => 'fixture']));
        $factory = $this->createMock(\MageOS\ShoppingFeed\Model\Generator\Factory::class);
        $error = new \RuntimeException('Internal connection details');
        $factory->method('create')->willThrowException($error);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->once())->method('critical')->with($error);
        foreach (['registry' => $registry, 'generatorFactory' => $factory, '_logger' => $logger] as $name => $value) {
            (new \ReflectionProperty($block, $name))->setValue($block, $value);
        }
        (new \ReflectionMethod($block, '_beforeToHtml'))->invoke($block);
        $messages = array_map('strval', $block->getMessages());
        $this->assertCount(2, $messages);
        $this->assertStringNotContainsString('Internal connection details', implode(' ', $messages));
    }
}
