<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_ShoppingFeed
 * @copyright Copyright (c) 2016 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */

namespace MageOS\ShoppingFeed\Model\Product;

class Filter
{
    /** @var array<string, list<array{column: string, operation: string, value: string}>>|null */
    private ?array $modifierChains = null;

    /**
     * Feed object
     *
     * @var \MageOS\ShoppingFeed\Model\Feed
     */
    protected $feed;

    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\Cache
     */
    protected $cache;

    /**
     * Product field filter constructor.
     *
     * @param \MageOS\ShoppingFeed\Model\Generator\Cache $cache
     */
    public function __construct(
        \MageOS\ShoppingFeed\Model\Generator\Cache $cache
    ) {

        $this->cache = $cache;
    }

    /**
     * Setter for Feed
     *
     * @param \MageOS\ShoppingFeed\Model\Feed $feed
     * @return $this
     */
    public function setFeed(\MageOS\ShoppingFeed\Model\Feed $feed)
    {
        $this->feed = $feed;
        $this->modifierChains = null;
        return $this;
    }

    /**
     * Cleans field.
     *
     * @param string $field
     * @param array<string,mixed>|null $params Column map entry; its column selects find/replace and limit rules
     * @return string
     */
    public function cleanField($field, $params = null)
    {
        if ($params !== null && array_key_exists('column', $params)) {
            $this->findAndReplace($field, $params['column']);
        }

        $isXml = $this->feed->getConfig('output_params_format', 'delimited') === 'xml';
        $charset = $isXml ? 'UTF-8' : $this->feed->getConfig('output_params_encoding', "UTF-8");
        if (extension_loaded('mbstring')) {
            $supportedEncodings = array_map('strtolower', mb_list_encodings());
            if (!is_string($charset) || !in_array(strtolower($charset), $supportedEncodings, true)) {
                $charset = 'UTF-8';
            }
            $sourceEncoding = mb_detect_encoding($field, mb_detect_order(), true);
            if ($sourceEncoding !== false) {
                $field = mb_convert_encoding($field, $charset, $sourceEncoding);
            }
        }

        $delimiter = (string)$this->feed->getConfig('output_params_delimiter', "\t");
        $delimiter_other = (string)$this->feed->getConfig('output_params_delimiter_other', "\t");
        $replacements = [
            "\n" => " ",
            "\r" => " ",
        ];
        $activeDelimiter = $delimiter == 'other' ? $delimiter_other : ($delimiter == '\t' ? "\t" : $delimiter);
        $isCustomCsv = $this->feed->getData('type') === 'generic' && $activeDelimiter === ',';
        $hasEnclosure = $isXml || $isCustomCsv
            || (string)$this->feed->getConfig('output_params_enclose_cell', '') !== '';
        if ($activeDelimiter !== '' && !$hasEnclosure) {
            $replacements[$activeDelimiter] = ' ';
        }
        $field = strtr($field, $replacements);

        $chain = $this->getModifierChains()[$params['column'] ?? ''] ?? [];
        $explicitMarkup = array_intersect(['strip_tags', 'escape'], array_column($chain, 'operation'));
        if (!$explicitMarkup) {
            // Page Builder HTML Code content can contain escaped tags and double-encoded entities.
            for ($pass = 0; $pass < 2; $pass++) {
                $field = $this->removeMarkup($field);
                if (extension_loaded('mbstring')) {
                    $field = preg_replace_callback(
                        "/(&#?[a-z0-9]{1,8};)/i",
                        [$this, 'htmlEntitiesToUtf8Callback'],
                        $field
                    );
                }
            }
            $field = $this->removeMarkup($field);
        }
        /** Decoding can reintroduce separators that were absent from the source text. */
        $separators = ["\n" => ' ', "\r" => ' '];
        if ($activeDelimiter !== '' && !$hasEnclosure) {
            $separators[$activeDelimiter] = ' ';
        }
        $field = strtr($field, $separators);
        $field = preg_replace('/\s\s+/', ' ', $field);
        $field = str_replace(PHP_EOL, "", $field);
        $field = trim($field);
        if (!$chain && $params !== null && array_key_exists('column', $params)) {
            $this->limitOutput($field, $params['column']);
        }

        return $field;
    }

    /**
     * Apply modifiers once to assembled rows, after directive formatting and before empty checks.
     *
     * @param array $rows
     * @phpstan-param array<array<string, mixed>> $rows
     * @param string $currency Effective store currency supplied by the adapter
     * @return array<array<string, mixed>>
     */
    public function modifyRows(array $rows, string $currency = ''): array
    {
        $chains = $this->getModifierChains();
        if (!$chains) {
            return $rows;
        }
        $modifier = new ModifierChain();
        $encoding = $this->feed->getConfig('output_params_format', 'delimited') === 'xml'
            ? 'UTF-8' : (string)$this->feed->getConfig('output_params_encoding', 'UTF-8');
        if (!in_array(strtolower($encoding), array_map('strtolower', mb_list_encodings()), true)) {
            $encoding = 'UTF-8';
        }
        foreach ($rows as &$row) {
            foreach ($chains as $column => $chain) {
                if (array_key_exists($column, $row)) {
                    $row[$column] = $modifier->apply(
                        $row[$column],
                        $chain,
                        $currency !== '' ? $currency : (string)$this->feed->getConfig('general_currency', ''),
                        $encoding
                    );
                    $row[$column] = $this->finishModifiedValue($row[$column], (string)$column, $encoding);
                }
            }
        }
        return $rows;
    }

    /**
     * Recheck output separators added by modifiers and apply the column limit last.
     *
     * @param mixed $value
     * @param string $column
     * @param string $encoding
     * @return mixed
     */
    private function finishModifiedValue($value, string $column, string $encoding)
    {
        if ($value instanceof \MageOS\ShoppingFeed\Model\Output\MappedValue) {
            return $value->map(
                fn (string $item): string => (string)$this->finishModifiedValue($item, $column, $encoding)
            );
        }
        if (is_array($value)) {
            return array_map(fn ($item) => $this->finishModifiedValue($item, $column, $encoding), $value);
        }
        $delimiter = (string)$this->feed->getConfig('output_params_delimiter', "\t");
        $delimiter = $delimiter === 'other'
            ? (string)$this->feed->getConfig('output_params_delimiter_other', "\t")
            : ($delimiter === '\t' ? "\t" : $delimiter);
        $replacements = ["\r" => ' ', "\n" => ' '];
        $enclosed = $this->feed->getConfig('output_params_format', 'delimited') === 'xml'
            || ($this->feed->getData('type') === 'generic' && $delimiter === ',')
            || (string)$this->feed->getConfig('output_params_enclose_cell', '') !== '';
        if ($delimiter !== '' && !$enclosed) {
            $replacements[$delimiter] = ' ';
        }
        $value = strtr($value, $replacements);
        $this->limitOutput($value, $column, $encoding);
        return $value;
    }

    /**
     * Compile once per feed binding, also rejecting invalid stored/imported configuration at runtime.
     *
     * @return array<string, list<array{column: string, operation: string, value: string}>>
     */
    private function getModifierChains(): array
    {
        return $this->modifierChains ??= (new ModifierChain())->compileForFeed($this->feed);
    }

    /**
     * Remove markup and executable block contents while retaining literal comparisons.
     *
     * @param string $field
     * @return string
     */
    private function removeMarkup(string $field): string
    {
        return (new Markup())->remove($field);
    }

    /**
     * Find a replace logic
     *
     * @param mixed $string Cell value, replaced in place
     * @param string|null $column Column name whose find/replace rules apply, besides the all-column rules
     * @return void
     */
    public function findAndReplace(&$string, $column)
    {
        $cacheKey = ['feed', $this->feed->getId(), 'find_and_replace'];
        if ($this->cache->getCache($cacheKey, true)) {
            $def = ['find' => [], 'replace' => []];
            $findAndReplace = ['-all-' => $def];

            $filterData = $this->feed->getConfig('filters_find_and_replace');

            if (is_array($filterData) && count($filterData) > 0) {
                foreach ($filterData as $item) {
                    if (empty($item['column'])) {
                        array_push($findAndReplace['-all-']['find'], $item['find']);
                        array_push($findAndReplace['-all-']['replace'], $item['replace']);
                    } else {
                        if (!array_key_exists($item['column'], $findAndReplace)) {
                            $findAndReplace[$item['column']] = $def;
                        }
                        array_push($findAndReplace[$item['column']]['find'], $item['find']);
                        array_push($findAndReplace[$item['column']]['replace'], $item['replace']);
                    }
                }
            }
            $this->cache->setCache($cacheKey, $findAndReplace);
        }
        $findAndReplace = $this->cache->getCache($cacheKey, []);

        $column = (string)$column;
        if (array_key_exists($column, $findAndReplace)) {
            $string = str_replace($findAndReplace[$column]['find'], $findAndReplace[$column]['replace'], $string);
        }
        if (!empty($findAndReplace['-all-']['find'])) {
            $string = str_replace($findAndReplace['-all-']['find'], $findAndReplace['-all-']['replace'], $string);
        }
    }

    /**
     * Truncate $string by column limit
     *
     * @param mixed $string Cell value, truncated in place
     * @param string|null $column Column name whose output limit applies
     * @param string $encoding Character encoding for the output length
     * @return void
     */
    public function limitOutput(&$string, $column, string $encoding = 'UTF-8')
    {
        $limitData = $this->feed->getConfig('filters_output_limit');
        if ($limitData !== null) {
            foreach ($limitData as $data) {
                $limit = (int) $data['limit'];
                if ($data['column'] == $column) {
                    if (mb_strlen($string, $encoding) > $limit) {
                        $string = mb_substr($string, 0, $limit, $encoding);
                    }
                    continue;
                }
            }
        }
    }

    /**
     * Decode one matched HTML entity to UTF-8.
     *
     * @param string[] $matches
     * @return string
     */
    public function htmlEntitiesToUtf8Callback($matches)
    {
        return mb_convert_encoding($matches[1], "UTF-8", "HTML-ENTITIES");
    }
}
