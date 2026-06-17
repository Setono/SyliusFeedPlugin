<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Mapping;

/**
 * How a field mapping obtains its raw value before transformation (§4.1).
 */
enum SourceType: string
{
    /** A named source field / value resolver (incl. attribute:{code}, lookup:{table}:{column}). */
    case FIELD = 'field';

    /** A static literal value. */
    case LITERAL = 'literal';

    /** A Symfony ExpressionLanguage string evaluated against the item (§10). */
    case EXPRESSION = 'expression';

    /** A sandboxed Twig template rendered against the item (§10). */
    case TWIG = 'twig';
}
