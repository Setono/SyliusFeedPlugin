<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Scripting;

use Setono\SyliusFeedPlugin\Lookup\LookupInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * ExpressionLanguage-backed evaluator. Sandboxed by design (no arbitrary PHP calls; only operators,
 * property/method access on the provided objects, and registered functions). Parsed expressions are
 * cached in-process by ExpressionLanguage, so repeating the same expression across a 50k-item run is
 * cheap. The `lookup(table, key, column)` function delegates to the {@see LookupInterface}.
 */
final class ExpressionEvaluator implements ExpressionEvaluatorInterface
{
    private readonly ExpressionLanguage $expressionLanguage;

    public function __construct(LookupInterface $lookup)
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->register(
            'lookup',
            // Compilation to PHP is not supported for this function; the evaluator path is used.
            static fn (string $table, string $key, string $column): string => 'null',
            static fn (array $variables, mixed $table, mixed $key, mixed $column): mixed => $lookup->get(
                is_scalar($table) ? (string) $table : '',
                $key,
                is_scalar($column) ? (string) $column : '',
            ),
        );
    }

    public function evaluate(string $expression, array $variables): mixed
    {
        return $this->expressionLanguage->evaluate($expression, $variables);
    }
}
