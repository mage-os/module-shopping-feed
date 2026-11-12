<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\Output;

use Magento\Framework\Exception\LocalizedException;

/** Apply a destination's RSS field contract after its normal row validation. */
class Rss extends Xml
{
    /** @var array<string,string> */
    private array $aliases;
    /** @var array<string,list<string>> */
    private array $compounds;
    /** @var string[] */
    private array $lists;
    /** @var string[] */
    private array $unprefixed;

    /**
     * @param array<string,mixed> $preset Canonical destination defaults, never saved wrapper overrides
     * @param string $title
     * @param string $link
     */
    public function __construct(array $preset, string $title, string $link)
    {
        $this->aliases = $preset['xml_field_aliases'] ?? [];
        $this->compounds = $preset['xml_compound_fields'] ?? [];
        $this->lists = array_filter(explode(',', $preset['xml_list_fields'] ?? ''));
        $this->unprefixed = array_filter(explode(',', $preset['xml_unprefixed_fields'] ?? ''));
        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- XML metadata requires ENT_XML1 escaping.
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        parent::__construct(
            strtr($preset['xml_header'], ['{{title}}' => $escape($title), '{{link}}' => $escape($link)]),
            $preset['xml_item'],
            $preset['xml_footer'],
            ['*']
        );
    }

    /**
     * Convert mapped columns to destination names, repeated fields and compound elements.
     *
     * @param array<string,mixed> $fields
     * @return string
     */
    public function row(array $fields): string
    {
        $output = [];
        foreach ($fields as $column => $value) {
            $name = $this->aliases[$column] ?? $column;
            $name = str_starts_with($name, 'g:') ? substr($name, 2) : $name;
            if (isset($this->compounds[$name])) {
                $groups = [];
                foreach ($this->splitValues($value) as $group) {
                    $parts = is_array($group) ? null : explode(':', $group);
                    $keys = $this->compounds[$name];
                    if ($parts !== null && count($parts) !== count($keys)) {
                        throw new InvalidValueException(__('Invalid XML compound value for %1.', $column));
                    }
                    $children = [];
                    foreach ($keys as $index => $key) {
                        $children['g:' . $key] = $parts === null ? ($group[$key] ?? '') : trim($parts[$index]);
                    }
                    $groups[] = $children;
                }
                $value = $groups;
            } elseif (in_array($name, $this->lists, true)) {
                $value = $this->splitValues($value);
            }
            $output[$this->qualifiedName($name)] = MappedValue::text($value);
        }
        return parent::row($output);
    }

    /**
     * Validate mapped destination names without requiring product values.
     *
     * @param array<scalar> $columns
     * @return void
     */
    public function validateColumns(array $columns): void
    {
        parent::validateColumns(array_map(function ($column): string {
            $column = (string)$column;
            $name = $this->aliases[$column] ?? $column;
            return $this->qualifiedName($name);
        }, $columns));
    }

    /**
     * Apply the provider's namespace rules to both saved and generated column names.
     *
     * @param string $name
     * @return string
     */
    private function qualifiedName(string $name): string
    {
        $name = str_starts_with($name, 'g:') ? substr($name, 2) : $name;
        return in_array($name, $this->unprefixed, true) ? $name : 'g:' . $name;
    }

    /**
     * Expand the existing mapper's comma-separated values and repeated column arrays.
     *
     * @param mixed $value
     * @return list<string|array<string,mixed>>
     */
    private function splitValues($value): array
    {
        $result = [];
        foreach (is_array($value) ? $value : [$value] as $item) {
            if ($item instanceof MappedValue) {
                array_push($result, ...$item->values);
                continue;
            }
            if ($item !== null && !is_scalar($item)) {
                throw new LocalizedException(__('XML preset values must be scalar.'));
            }
            foreach (explode(',', (string)$item) as $part) {
                if (trim($part) !== '') {
                    $result[] = trim($part);
                }
            }
        }
        return $result;
    }
}
