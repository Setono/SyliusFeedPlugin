<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Scripting;

use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Sandbox policy for admin-authored `twig` transformation templates (§10). Admin templates are
 * untrusted and run per item, so:
 *  - only a curated allowlist of tags/filters/functions is permitted (delegated to Twig's
 *    {@see SecurityPolicy});
 *  - method calls are restricted to read-only accessors (`get*`/`is*`/`has*`/`__toString`) so a
 *    template can read data off the entity but cannot invoke mutating/side-effecting methods;
 *  - reading properties is permitted (there is no write surface from a template).
 */
final class FeedTemplateSecurityPolicy implements SecurityPolicyInterface
{
    private readonly SecurityPolicy $delegate;

    public function __construct()
    {
        $this->delegate = new SecurityPolicy(
            allowedTags: ['if', 'for', 'set', 'apply', 'filter'],
            allowedFilters: [
                'upper', 'lower', 'title', 'capitalize', 'trim', 'nl2br', 'striptags',
                'escape', 'e', 'raw', 'replace', 'split', 'join', 'slice', 'default',
                'format', 'number_format', 'date', 'abs', 'round', 'first', 'last',
                'length', 'keys', 'merge', 'reverse', 'url_encode', 'json_encode', 'spaceless',
            ],
            allowedMethods: [],
            allowedProperties: [],
            allowedFunctions: ['lookup', 'max', 'min', 'range', 'cycle', 'date'],
        );
    }

    /**
     * @param string[] $tags
     * @param string[] $filters
     * @param string[] $functions
     */
    public function checkSecurity($tags, $filters, $functions): void
    {
        $this->delegate->checkSecurity($tags, $filters, $functions);
    }

    /**
     * @param object $obj
     * @param string $method
     */
    public function checkMethodAllowed($obj, $method): void
    {
        $normalized = strtolower($method);
        if (str_starts_with($normalized, 'get') ||
            str_starts_with($normalized, 'is') ||
            str_starts_with($normalized, 'has') ||
            '__tostring' === $normalized
        ) {
            return;
        }

        throw new SecurityNotAllowedMethodError(
            sprintf('Calling "%s" method on a "%s" object is not allowed in a feed template.', $method, $obj::class),
            $obj::class,
            $method,
        );
    }

    /**
     * @param object $obj
     * @param string $property
     */
    public function checkPropertyAllowed($obj, $property): void
    {
        // Reading a public property off the provided objects is permitted; templates cannot write.
    }
}
