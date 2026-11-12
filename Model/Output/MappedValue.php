<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\Output;

/** Keep mapper boundaries intact while retaining the existing scalar validation and preview value. */
class MappedValue implements \Stringable
{
    /**
     * @param array<mixed> $values Repeated scalar values or compound groups
     * @param string $text Existing delimited representation
     */
    public function __construct(public readonly array $values, private readonly string $text)
    {
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
