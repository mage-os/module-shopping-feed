<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\Product;

/** Shared markup removal for default cleaning and explicit Strip tags; entities are preserved. */
class Markup
{
    /**
     * Remove tags and executable blocks, leaving spaces at text boundaries.
     *
     * @param string $field
     * @return string
     */
    public function remove(string $field): string
    {
        // Discard unfinished tags before stripping later complete tags, preserving their text.
        $field = preg_replace(
            '~</?[a-z][a-z0-9:-]*(?=[\s/>]|$)[^<>]*(?=</?[a-z][a-z0-9:-]*(?=[\s/>]|$)|$)~i',
            ' ',
            $field
        ) ?? '';
        $field = preg_replace('~<(script|style)\b[^>]*>.*?(?:</\1\s*>|$)~is', ' ', $field) ?? '';
        $tags = <<<'REGEX'
~
    <!--.*?(?:-->|$)
    | <![a-z][^>]*>
    | </?[a-z][a-z0-9:-]*(?=[\s/>])
      (?:
          [^>"']
          | "(?:[^"<]|<(?!/?[a-z][a-z0-9:-]*(?=[\s/>])|[!?]))*"
          | '(?:[^'<]|<(?!/?[a-z][a-z0-9:-]*(?=[\s/>])|[!?]))*'
      )*>
~isx
REGEX;
        $field = preg_replace($tags, ' ', $field) ?? '';
        return preg_replace('~</?[a-z][a-z0-9:-]*(?=[\s/>])[^<>]*>~i', ' ', $field) ?? '';
    }
}
