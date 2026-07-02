<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluatorInterface;
use Setono\SyliusFeedPlugin\Scripting\ScriptingVariables;

/**
 * Evaluates a Symfony ExpressionLanguage string over the value + item for typed/boolean/derived
 * logic (§10), e.g. `fields['price'] - fields['cost'] > 100 ? 'high' : 'low'`. Structural: operates
 * on the value as a whole. An evaluation error is a no-op (returns the value unchanged) so one bad
 * expression can never abort the run.
 */
final class Expression implements TransformationInterface
{
    public const TYPE = 'expression';

    public function __construct(private readonly ExpressionEvaluatorInterface $evaluator)
    {
    }

    public static function of(string $expression): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['expression' => $expression]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        $expression = $params['expression'] ?? null;
        if (!is_string($expression) || '' === $expression) {
            return $value;
        }

        try {
            return $this->evaluator->evaluate($expression, ScriptingVariables::for($item, $value));
        } catch (\Throwable) {
            return $value;
        }
    }
}
