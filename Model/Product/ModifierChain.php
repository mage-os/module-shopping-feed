<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\Product;

use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\Feed;

/** Validated, ordered transformations of final column values. No executable configuration. */
class ModifierChain
{
    public const CONFIG_KEY = 'filters_column_modifiers';
    public const OPERATIONS = [
        'prepend', 'append', 'lowercase', 'uppercase', 'titlecase', 'round', 'integer',
        'decimal', 'date', 'price', 'strip_tags', 'escape', 'secure_url'
    ];

    /**
     * Validate the same contract for saves, imports and generation; retain literal text exactly.
     *
     * @param mixed $rows
     * @param Feed|null $feed
     * @return array<string, list<array{column: string, operation: string, value: string}>>
     * @throws LocalizedException
     */
    public function compile(mixed $rows, ?Feed $feed = null): array
    {
        if ($rows === null || $rows === '') {
            return [];
        }
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new LocalizedException(__('Column modifiers must be a list of rows.'));
        }
        if (count($rows) > 200) {
            throw new LocalizedException(__('Column modifiers cannot exceed 200 rows.'));
        }
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_string($row['column'] ?? null) || trim($row['column']) === ''
                || !in_array($row['operation'] ?? null, self::OPERATIONS, true)
                || (!is_string($row['value'] ?? '') && !is_int($row['value'] ?? ''))
            ) {
                throw new LocalizedException(__('Each column modifier requires a column, operation and text value.'));
            }
            $operation = $row['operation'];
            $value = (string)($row['value'] ?? '');
            if (strlen($value) > 4096 || strlen($row['column']) > 255) {
                throw new LocalizedException(__('Column modifier text is too long.'));
            }
            if (in_array($operation, ['round', 'decimal'], true)
                && !preg_match('/^(?:[0-9]|1[0-2])$/D', $value)
            ) {
                throw new LocalizedException(__('Round and Decimal places require a value from 0 to 12.'));
            }
            if ($operation === 'date' && ($value === '' || strlen($value) > 128
                || preg_match('/[\x00-\x1f\x7f]/', $value))
            ) {
                throw new LocalizedException(__('Date format requires a PHP date format of at most 128 characters.'));
            }
            if ($operation === 'price' && $value !== '' && !preg_match('/^[A-Z]{3}$/D', $value)) {
                throw new LocalizedException(__(
                    'Price format accepts a three-letter uppercase currency code or blank.'
                ));
            }
            $column = $feed ? $feed->resolveColumnName($row['column']) : $row['column'];
            $result[$column][] = ['column' => $column, 'operation' => $operation, 'value' => $value];
        }
        return $result;
    }

    /**
     * Compile stored rows and reject output settings that would destroy escaped entities.
     *
     * @param Feed $feed
     * @return array<string, list<array{column: string, operation: string, value: string}>>
     * @throws LocalizedException
     */
    public function compileForFeed(Feed $feed): array
    {
        $chains = $this->compile($feed->getData('config', self::CONFIG_KEY), $feed);
        if (!$chains) {
            return $chains;
        }
        $delimiter = (string)$feed->getConfig('output_params_delimiter', "\t");
        $delimiter = $delimiter === 'other'
            ? (string)$feed->getConfig('output_params_delimiter_other', "\t")
            : ($delimiter === '\t' ? "\t" : $delimiter);
        $enclosed = ($feed->getData('type') === 'generic' && $delimiter === ',')
            || (string)$feed->getConfig('output_params_enclose_cell', '') !== '';
        // A delimiter may overlap an entity boundary, e.g. "; " or "|&". Require enclosure
        // whenever any character belongs to the entity alphabet, including numeric references.
        if (!$enclosed && preg_match('/[a-z0-9&#;]/i', $delimiter)) {
            foreach ($chains as $chain) {
                if (in_array('escape', array_column($chain, 'operation'), true)) {
                    throw new LocalizedException(__(
                        'Escape HTML requires a cell enclosure when the delimiter contains '
                        . 'letters, digits, &, # or ;. Set a cell enclosure or choose another delimiter.'
                    ));
                }
            }
        }
        return $chains;
    }

    /**
     * Transform one scalar or every member of a repeated column, preserving its shape.
     *
     * @param mixed $value
     * @param array $chain
     * @phpstan-param list<array{column: string, operation: string, value: string}> $chain
     * @param string $currency
     * @param string $encoding
     * @return mixed
     */
    public function apply(mixed $value, array $chain, string $currency, string $encoding = 'UTF-8'): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->apply($item, $chain, $currency, $encoding), $value);
        }
        $value = mb_convert_encoding((string)$value, 'UTF-8', $encoding);
        if ($value === false) {
            throw new LocalizedException(__('Column modifier encoding conversion failed.'));
        }
        foreach ($chain as $row) {
            $argument = $row['value'];
            $value = match ($row['operation']) {
                'prepend' => $argument . $value,
                'append' => $value . $argument,
                'lowercase' => mb_strtolower($value, 'UTF-8'),
                'uppercase' => mb_strtoupper($value, 'UTF-8'),
                'titlecase' => mb_convert_case($value, MB_CASE_TITLE, 'UTF-8'),
                'strip_tags' => $this->stripTags($value),
                // phpcs:ignore Magento2.Functions.DiscouragedFunction -- Feed entities must remain single-encoded.
                'escape' => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false),
                'secure_url' => $this->secureUrl($value),
                'date' => $this->formatDate($value, $argument),
                default => $this->formatNumber($value, $row['operation'], $argument, $currency)
            };
        }
        return mb_convert_encoding($value, $encoding, 'UTF-8');
    }

    /**
     * Numeric modifiers accept plain numbers and the ISO currency suffix used by price directives.
     *
     * Invalid or blank input is preserved rather than silently becoming zero.
     *
     * @param string $value
     * @param string $operation
     * @param string $argument
     * @param string $currency
     * @return string
     */
    private function formatNumber(string $value, string $operation, string $argument, string $currency): string
    {
        if (!preg_match('/^([+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?)(?: ([A-Z]{3}))?$/D', $value, $parts)
            || !is_finite((float)$parts[1])
        ) {
            return $value;
        }
        $number = (float)$parts[1];
        $suffix = isset($parts[2]) ? ' ' . $parts[2] : '';
        if ($operation === 'price') {
            $code = $argument !== '' ? $argument : ($parts[2] ?? $currency);
            return number_format($number, 2, '.', '') . ($code !== '' ? ' ' . $code : '');
        }
        if ($operation === 'decimal') {
            return number_format($number, (int)$argument, '.', '') . $suffix;
        }
        if ($operation === 'integer') {
            return number_format($number < 0 ? ceil($number) : floor($number), 0, '.', '') . $suffix;
        }
        $rounded = number_format($number, (int)$argument, '.', '');
        if ((int)$argument > 0) {
            $rounded = rtrim(rtrim($rounded, '0'), '.');
        }
        return $rounded . $suffix;
    }

    /**
     * Discard executable block contents as the default cleaner does, then remove ordinary tags.
     *
     * @param string $value
     * @return string
     */
    private function stripTags(string $value): string
    {
        return trim(preg_replace('/\s\s+/', ' ', (new Markup())->remove($value)) ?? '');
    }

    /**
     * Parse only absolute ISO dates/times; never accept relative phrases or normalize impossible dates.
     *
     * Missing time and timezone mean midnight UTC. Explicit offsets are retained.
     *
     * @param string $value
     * @param string $format
     * @return string
     */
    private function formatDate(string $value, string $format): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})?)?$/D', $value)) {
            return $value;
        }
        try {
            $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
            $errors = \DateTimeImmutable::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) {
                return $value;
            }
            return $date->format($format);
        } catch (\Exception $exception) {
            return $value;
        }
    }

    /**
     * Rewrite only the leading HTTP scheme (or network-path reference); leave paths and queries intact.
     *
     * @param string $value
     * @return string
     */
    private function secureUrl(string $value): string
    {
        if (!preg_match('~^(?:https?://|//)[^/\s?#]+(?:[/?#][^\s]*)?$~iD', $value)) {
            return $value;
        }
        return str_starts_with($value, '//')
            ? 'https:' . $value : (string)preg_replace('~^http://~i', 'https://', $value);
    }
}
