<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Mapping;

/**
 * A single entry in a field's transformation chain: {type, params} (§10).
 *
 * This is the canonical in-memory descriptor produced both by mapping-preset builders
 * (e.g. Truncate::chars(150)) and by hydrating the JSON stored on a FeedField.
 */
final class TransformationConfig
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        private readonly string $type,
        private readonly array $params = [],
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
