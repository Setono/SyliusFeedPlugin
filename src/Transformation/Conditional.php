<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistryInterface;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolverInterface;

/**
 * Evaluates a condition (via the shared operator vocabulary) and branches into a `then`/`else`
 * action: `set` a resolved value, `skip` the item's field entirely (emit nothing), or `continue`
 * with the value unchanged. A missing branch (no match and no `else`) also continues unchanged.
 * Structural: operates on the value as a whole, and only affects its own field (§10).
 */
final class Conditional implements TransformationInterface
{
    public const TYPE = 'conditional';

    public const ACTION_SET = 'set';

    public const ACTION_SKIP = 'skip';

    public const ACTION_CONTINUE = 'continue';

    public function __construct(
        private readonly ReferenceResolverInterface $referenceResolver,
        private readonly OperatorRegistryInterface $operatorRegistry,
    ) {
    }

    /**
     * @param array<string, mixed> $if
     * @param array<string, mixed> $then
     * @param array<string, mixed>|null $else
     */
    public static function of(array $if, array $then, ?array $else = null): TransformationConfig
    {
        $params = ['if' => $if, 'then' => $then];

        if (null !== $else) {
            $params['else'] = $else;
        }

        return new TransformationConfig(self::TYPE, $params);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        $condition = $params['if'] ?? null;
        $then = $params['then'] ?? null;

        if (!is_array($condition) || !is_array($then)) {
            return $value;
        }

        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;

        if (!is_string($field) || !is_string($operator)) {
            return $value;
        }

        $left = $this->referenceResolver->resolve($field, $value, $item);

        $operand = null;
        if (array_key_exists('value', $condition)) {
            $operandReference = $condition['value'];
            $operand = is_string($operandReference) ? $this->referenceResolver->resolve($operandReference, $value, $item) : null;
        }

        $matched = $this->operatorRegistry->get($operator)->matches($left, ['value' => $operand]);

        $branch = $matched ? $then : ($params['else'] ?? null);
        if (!is_array($branch)) {
            return $value;
        }

        $action = $branch['action'] ?? null;

        return match ($action) {
            self::ACTION_SET => $this->resolveBranchValue($branch, $value, $item),
            self::ACTION_SKIP => null,
            self::ACTION_CONTINUE => $value,
            default => $value,
        };
    }

    private function resolveBranchValue(array $branch, mixed $value, FeedItem $item): mixed
    {
        $reference = $branch['value'] ?? null;
        if (!is_string($reference)) {
            return $value;
        }

        return $this->referenceResolver->resolve($reference, $value, $item);
    }
}
