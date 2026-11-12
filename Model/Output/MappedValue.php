<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\Output;

/** Keep mapper boundaries intact while retaining the existing scalar validation and preview value. */
class MappedValue implements \Stringable
{
    /**
     * @param array<mixed> $values Repeated scalar values or compound groups
     * @param string $text Existing delimited representation
     * @param string $separator Separator for the scalar preview of repeated values
     */
    public function __construct(
        public readonly array $values,
        private readonly string $text,
        private readonly string $separator = ','
    ) {
    }

    /**
     * Retain the scalar cell used by validators and previews.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->text;
    }

    /**
     * Transform each scalar leaf without flattening list or compound boundaries, or mutating cached values.
     *
     * @param callable(string):string $transform
     * @return self
     */
    public function map(callable $transform): self
    {
        $values = $this->mapValues($this->values, $transform);
        return new self($values, $this->joinValues($values, $this->separator), $this->separator);
    }

    /**
     * Preserve array keys and nested groups while applying a scalar transformation.
     *
     * @param array<mixed> $values
     * @param callable(string):string $transform
     * @return array<mixed>
     */
    private function mapValues(array $values, callable $transform): array
    {
        return array_map(fn ($value) => is_array($value)
            ? $this->mapValues($value, $transform) : $transform((string)$value), $values);
    }

    /**
     * Preserve the existing comma-list and colon-compound representation for previews and validators.
     *
     * @param array<mixed> $values
     * @param string $separator
     * @return string
     */
    private function joinValues(array $values, string $separator): string
    {
        return implode($separator, array_map(fn ($value): string => is_array($value)
            ? $this->joinValues($value, ':') : (string)$value, $values));
    }

    /**
     * Project typed values for existing destination validators and preview tables.
     *
     * @param mixed $value
     * @return mixed
     */
    // phpcs:ignore Magento2.Functions.StaticFunction -- Pure recursive value conversion; no service state.
    public static function text(mixed $value): mixed
    {
        return is_array($value) ? array_map([self::class, 'text'], $value)
            : ($value instanceof self ? (string)$value : $value);
    }
}
