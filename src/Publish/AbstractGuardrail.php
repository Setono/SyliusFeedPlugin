<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

/**
 * Base for guardrails that read numeric thresholds from the untrusted, JSON-sourced params map.
 * Coerces values defensively so a malformed config yields the default rather than a type error.
 */
abstract class AbstractGuardrail implements GuardrailInterface
{
    /**
     * @param array<string, mixed> $params
     */
    protected function intParam(array $params, string $key, int $default = 0): int
    {
        $value = $params[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function floatParam(array $params, string $key, float $default = 0.0): float
    {
        $value = $params[$key] ?? $default;

        return is_numeric($value) ? (float) $value : $default;
    }
}
