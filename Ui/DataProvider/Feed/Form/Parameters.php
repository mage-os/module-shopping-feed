<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form;

use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\FeedTypes\Config;

/** Declarative replacements for the bundled PHP parameter renderers. */
class Parameters
{
    /**
     * Constructor
     *
     * @param Config $types
     * @param Options $options
     * @param ConditionalValue $conditionalValue
     * @param array $renderers
     */
    public function __construct(
        private Config $types,
        private Options $options,
        private ConditionalValue $conditionalValue,
        private array $renderers = []
    ) {
    }

    /**
     * Build the UI component parameter definitions for each directive of the feed type.
     *
     * @param Feed $feed
     * @return array
     */
    public function get(Feed $feed): array
    {
        $result = [];
        foreach ($this->types->getDirectives($feed->getType()) as $code => $directive) {
            $renderer = $directive['renderer'] ?? '';
            $renderer = is_array($renderer) ? ($renderer['type'] ?? '') : $renderer;
            $definition = $this->renderers[$renderer] ?? [
                'kind' => 'unsupported',
                // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                'notice' => __('This custom parameter is preserved. Its extension needs a UI component parameter definition to edit it.')
            ];
            if ($renderer === '') {
                $definition = ['kind' => 'none'];
            }
            if (isset($definition['source'])) {
                $definition['options'] = $this->options->get($definition['source']);
                unset($definition['source']);
            }
            if ($definition['kind'] === 'conditional') {
                $definition['conditions'] = $this->conditionalValue->get();
                $definition['attributes'] = $this->options->get('Attributes');
            }
            $definition['default'] = $directive['param'] ?? '';
            if ($definition['kind'] === 'none') {
                // Translate the help text as collected from the feed XML, markup included, then strip the markup.
                $help = (string)($directive['param'] ?? '');
                // phpcs:ignore Magento2.Functions.DiscouragedFunction -- decodes XML help text; no framework decoder
                $definition['notice'] = trim(html_entity_decode(
                    strip_tags($help === '' ? '' : (string)__($help)),
                    ENT_QUOTES,
                    'UTF-8'
                ));
            }
            foreach (['label', 'notice'] as $key) {
                if (isset($definition[$key]) && is_string($definition[$key])) {
                    $definition[$key] = __($definition[$key]);
                }
            }
            $result[$code] = $definition;
        }
        return $result;
    }
}
