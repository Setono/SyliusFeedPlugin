<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\Scripting;

use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusFeedPlugin\Model\LookupTable;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluatorInterface;
use Setono\SyliusFeedPlugin\Scripting\TwigTemplateRendererInterface;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;

/**
 * Proves the M4 scripting services (§10) are wired together and resolve `lookup(...)` through the
 * DB-backed {@see \Setono\SyliusFeedPlugin\Lookup\DatabaseLookup}: a persisted LookupTable enriches
 * both the expression evaluator and the sandboxed Twig renderer.
 */
final class ScriptingTest extends FunctionalTestCase
{
    private function persistBadges(): void
    {
        $table = new LookupTable();
        $table->setCode('badges');
        $table->setSourceType(LookupTable::SOURCE_TYPE_CSV);
        $table->setRows(['SKU-1' => ['suffix' => 'Bestseller']]);

        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);
        $manager = $registry->getManagerForClass(LookupTable::class);
        self::assertNotNull($manager);
        $manager->persist($table);
        $manager->flush();
    }

    /**
     * @test
     */
    public function it_resolves_a_lookup_in_an_expression_through_the_database(): void
    {
        $this->persistBadges();

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
    public function it_resolves_a_lookup_in_a_sandboxed_twig_template_through_the_database(): void
    {
        $this->persistBadges();

        $renderer = self::getContainer()->get(TwigTemplateRendererInterface::class);
        self::assertInstanceOf(TwigTemplateRendererInterface::class, $renderer);

        $result = $renderer->render(
            '{{ value ~ " " ~ lookup("badges", "SKU-1", "suffix") }}',
            ['value' => 'Shoe'],
        );

        self::assertSame('Shoe Bestseller', $result);
    }
}
