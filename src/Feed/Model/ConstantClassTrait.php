<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model;

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

    /**
     * Returns an array of possible values
     */
    abstract public static function getValues(): array;

    /**
     * Will return an instance based on the $value
     *
     * @param object|string|int $value
     */
    abstract public static function fromValue($value);

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
