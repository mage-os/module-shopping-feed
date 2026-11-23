<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\Output;

/** Bad mapped product data can skip a row; configuration and I/O errors must stop generation. */
class InvalidValueException extends \Magento\Framework\Exception\LocalizedException
{
}
