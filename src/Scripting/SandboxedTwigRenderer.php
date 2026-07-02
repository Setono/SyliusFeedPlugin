<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Scripting;

use Setono\SyliusFeedPlugin\Lookup\LookupInterface;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\TemplateWrapper;
use Twig\TwigFunction;

/**
 * Renders admin-authored templates in a dedicated, globally-sandboxed Twig environment (§10):
 *  - autoescape OFF — the writers already XML/CSV-escape on output, so escaping here would
 *    double-encode (`&` → `&amp;`);
 *  - the {@see FeedTemplateSecurityPolicy} allowlist is enforced for every template;
 *  - templates are compiled once and cached in-process by source string, so a 50k-item run does
 *    not recompile per item;
 *  - the `lookup(table, key, column)` function is available, mirroring the expression surface.
 */
final class SandboxedTwigRenderer implements TwigTemplateRendererInterface
{
    private readonly Environment $twig;

    /** @var array<string, TemplateWrapper> compiled templates keyed by source string */
    private array $templates = [];

    public function __construct(SecurityPolicyInterface $securityPolicy, LookupInterface $lookup)
    {
        $this->twig = new Environment(new ArrayLoader(), [
            'autoescape' => false,
            'cache' => false,
            'strict_variables' => false,
        ]);
        $this->twig->addExtension(new SandboxExtension($securityPolicy, true));
        $this->twig->addFunction(new TwigFunction(
            'lookup',
            static fn (string $table, mixed $key, string $column): mixed => $lookup->get($table, $key, $column),
        ));
    }

    public function render(string $template, array $variables): string
    {
        return $this->template($template)->render($variables);
    }

    private function template(string $source): TemplateWrapper
    {
        return $this->templates[$source] ??= $this->twig->createTemplate($source);
    }
}
