<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\Output;

use Magento\Framework\Exception\LocalizedException;

/** Serializes one item at a time so batches do not retain a feed document in memory. */
class Xml
{
    /**
     * @param string $header Opening document markup, including namespace declarations
     * @param string $item Qualified item element name
     * @param string $footer Closing document markup
     * @param string[] $optional Columns omitted when empty; * selects all columns
     * @param string[] $cdata Columns serialized as CDATA; * selects all columns
     */
    public function __construct(
        private readonly string $header,
        private readonly string $item,
        private readonly string $footer,
        private readonly array $optional = [],
        private readonly array $cdata = []
    ) {
        $this->validateName($item);
        if (preg_match('/<!\s*(?:DOCTYPE|ENTITY)/i', $header . $footer)) {
            throw new LocalizedException(__('XML output does not allow DTD or entity declarations.'));
        }
        $empty = $this->validateDocument($header . $footer);
        $probe = $this->validateDocument($header . "<$item/><$item/>" . $footer);
        if ($probe->getElementsByTagName('*')->length !== $empty->getElementsByTagName('*')->length + 2) {
            throw new LocalizedException(__('XML wrappers must contain item elements as markup.'));
        }
    }

    /**
     * Return the document boundary markup.
     *
     * @return string
     */
    public function header(): string
    {
        return $this->header . "\n";
    }

    /**
     * Return the document boundary markup.
     *
     * @return string
     */
    public function footer(): string
    {
        return $this->footer . "\n";
    }

    /**
     * Serialize mapped fields, preserving repeated values.
     *
     * @param array<string,mixed> $fields Mapped values in column order
     * @return string
     * @throws LocalizedException
     */
    public function row(array $fields): string
    {
        $xml = '<' . $this->item . '>' . $this->renderFields($fields) . '</' . $this->item . ">\n";
        $this->validateDocument($this->header . $xml . $this->footer);
        return $xml;
    }

    /**
     * Validate even optional columns against the wrapper namespace declarations before saving.
     *
     * @param array<scalar> $columns
     * @return void
     */
    public function validateColumns(array $columns): void
    {
        $markup = '';
        foreach ($columns as $name) {
            $this->validateName((string)$name);
            $markup .= '<' . $name . '/>';
        }
        $this->validateDocument($this->header . '<' . $this->item . '>' . $markup
            . '</' . $this->item . '>' . $this->footer);
    }

    /**
     * Render scalar fields and explicitly nested groups without accepting raw XML values.
     *
     * @param array<string,mixed> $fields
     * @return string
     */
    private function renderFields(array $fields): string
    {
        $xml = '';
        foreach ($fields as $name => $values) {
            $name = (string)$name;
            $this->validateName($name);
            $values = MappedValue::text($values);
            foreach (is_array($values) ? $values : [$values] as $value) {
                if (is_array($value)) {
                    $xml .= '<' . $name . '>' . $this->renderFields($value) . '</' . $name . '>';
                    continue;
                }
                if ($value !== null && !is_scalar($value)) {
                    throw new LocalizedException(__('XML values must be scalar: %1.', $name));
                }
                $value = $value === false ? '0' : (string)$value;
                if ($value === '' && $this->selected($name, $this->optional)) {
                    continue;
                }
                $characters = '/^[\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]*$/uD';
                if (preg_match($characters, $value) !== 1) {
                    throw new InvalidValueException(__('Invalid UTF-8 or XML character in column %1.', $name));
                }
                // phpcs:ignore Magento2.Functions.DiscouragedFunction -- XML requires ENT_XML1, not HTML escaping.
                $text = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                if ($this->selected($name, $this->cdata)) {
                    $text = '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $value) . ']]>';
                }
                $xml .= '<' . $name . '>' . $text . '</' . $name . '>';
            }
        }
        return $xml;
    }

    /**
     * Check whether a column was selected.
     *
     * @param string $name
     * @param string[] $columns
     * @return bool
     */
    private function selected(string $name, array $columns): bool
    {
        return in_array('*', $columns, true) || in_array($name, $columns, true);
    }

    /**
     * Reject names that cannot be safely used as qualified element names.
     *
     * @param string $name
     * @return void
     * @throws LocalizedException
     */
    private function validateName(string $name): void
    {
        // XML 1.0 (fifth edition) NCName ranges, with at most one namespace separator.
        $start = 'A-Z_a-z\\x{C0}-\\x{D6}\\x{D8}-\\x{F6}\\x{F8}-\\x{2FF}\\x{370}-\\x{37D}'
            . '\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}\\x{2070}-\\x{218F}\\x{2C00}-\\x{2FEF}'
            . '\\x{3001}-\\x{D7FF}\\x{F900}-\\x{FDCF}\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}';
        $part = '[' . $start . '][' . $start . '0-9.\\x{B7}\\x{300}-\\x{36F}\\x{203F}-\\x{2040}-]*';
        if (preg_match('/^' . $part . '(?::' . $part . ')?$/uD', $name) !== 1) {
            throw new LocalizedException(__('Invalid XML element name: %1.', $name));
        }
    }

    /**
     * Validate without loading external resources or expanding entities.
     *
     * @param string $xml
     * @return \DOMDocument
     * @throws LocalizedException
     */
    private function validateDocument(string $xml): \DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        try {
            libxml_clear_errors();
            $document = new \DOMDocument();
            $valid = $document->loadXML($xml, LIBXML_NONET);
            if (!$valid || libxml_get_errors() || $document->xmlVersion !== '1.0'
                || ($document->encoding !== null && strcasecmp($document->encoding, 'UTF-8') !== 0)
            ) {
                throw new LocalizedException(__('XML output must be well-formed XML 1.0 with UTF-8 encoding.'));
            }
            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
