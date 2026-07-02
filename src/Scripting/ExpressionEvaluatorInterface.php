<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Scripting;

/**
 * Evaluates a Symfony ExpressionLanguage string for typed/boolean logic (§10) — conditions,
 * numeric/derived values, anything that must return a bool/int/array. Variables are supplied by
 * {@see ScriptingVariables}; the `lookup(table, key, column)` function is available.
 */
interface ExpressionEvaluatorInterface
{
    /**
     * @param array<string, mixed> $variables
     */
    public function evaluate(string $expression, array $variables): mixed;
}
