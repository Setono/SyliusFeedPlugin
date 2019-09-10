<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model;

use Webmozart\Assert\Assert;

/**
 * This is taken directly from the excellent post by Matthias Pigulla here:
 *
 * https://www.webfactory.de/blog/expressive-type-checked-constants-for-php
 *
 * I have extended it to better fit the use of this plugin
 */
trait ConstantClassTrait
{
    /** @var object[] */
    private static $instances = [];

    /** @var string|int */
    private $value;

    /**
     * @param string|int $value
     */
    final private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param string|int $value
     */
    private static function constant($value): self
    {
        return self::$instances[$value] ?? self::$instances[$value] = new self($value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Will return an instance based on the $value
     *
     * @param object|string|int $value
     */
    public static function fromValue($value): self
    {
        if (is_object($value)) {
            $value = (string) $value;
        }

        Assert::oneOf($value, self::getValues());

        return self::constant($value);
    }

    /**
     * Returns an array of possible values
     */
    abstract public static function getValues(): array;
}
