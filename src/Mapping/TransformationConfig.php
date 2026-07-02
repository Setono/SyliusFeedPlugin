<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Mapping;

use Webmozart\Assert\Assert;

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

    /**
     * Hydrate from the `{type, params}` JSON stored on a FeedField.
     *
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $type = $data['type'] ?? null;
        Assert::string($type, 'A transformation config must have a string "type"');

        $params = $data['params'] ?? [];
        Assert::isArray($params, 'A transformation config\'s "params" must be an array');

        $typedParams = [];
        foreach ($params as $key => $value) {
            $typedParams[(string) $key] = $value;
        }

        return new self($type, $typedParams);
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

    /**
     * The `{type, params}` shape persisted on a FeedField.
     *
     * @return array{type: string, params: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['type' => $this->type, 'params' => $this->params];
    }
}
