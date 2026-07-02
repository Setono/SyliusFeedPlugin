<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Scripting;

/**
 * Renders a sandboxed Twig template for rich string assembly (§10) — titles, descriptions,
 * multi-part values. Autoescape is off (writers escape on output), templates are compiled once and
 * cached by source, and the same variables/functions as {@see ExpressionEvaluatorInterface} are
 * available. Output is always a string.
 */
interface TwigTemplateRendererInterface
{
    /**
     * @param array<string, mixed> $variables
     */
    public function render(string $template, array $variables): string;
}
