<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\Scripting;

use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;
use Setono\SyliusFeedPlugin\Lookup\LookupInterface;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluatorInterface;
use Setono\SyliusFeedPlugin\Scripting\TwigTemplateRendererInterface;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;

/**
 * Proves the M4 scripting services (§10) are wired together correctly: the expression evaluator and
 * the sandboxed Twig renderer both resolve `lookup(...)` calls through the very same lookup instance
 * the container hands out elsewhere (the {@see LookupInterface} alias is a singleton). Does not touch
 * the database.
 */
final class ScriptingTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_shares_a_single_lookup_instance_across_the_scripting_services(): void
    {
        $lookup = self::getContainer()->get(LookupInterface::class);

        self::assertInstanceOf(InMemoryLookup::class, $lookup);

        $lookup->addTable('badges', ['SKU-1' => ['suffix' => 'Bestseller']]);

        $evaluator = self::getContainer()->get(ExpressionEvaluatorInterface::class);
        self::assertInstanceOf(ExpressionEvaluatorInterface::class, $evaluator);

        self::assertSame(
            'Bestseller',
            $evaluator->evaluate("lookup('badges', 'SKU-1', 'suffix')", []),
        );
    }

    /**
     * @test
     */
    public function it_renders_a_template_that_looks_up_a_shared_table(): void
    {
        $lookup = self::getContainer()->get(LookupInterface::class);
        self::assertInstanceOf(InMemoryLookup::class, $lookup);

        $lookup->addTable('badges', ['SKU-1' => ['suffix' => 'Bestseller']]);

        $renderer = self::getContainer()->get(TwigTemplateRendererInterface::class);
        self::assertInstanceOf(TwigTemplateRendererInterface::class, $renderer);

        $result = $renderer->render(
            '{{ value ~ " " ~ lookup("badges", "SKU-1", "suffix") }}',
            ['value' => 'Shoe'],
        );

        self::assertSame('Shoe Bestseller', $result);
    }
}
