<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Scripting;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;
use Setono\SyliusFeedPlugin\Scripting\FeedTemplateSecurityPolicy;
use Setono\SyliusFeedPlugin\Scripting\SandboxedTwigRenderer;
use Twig\Sandbox\SecurityError;

final class SandboxedTwigRendererTest extends TestCase
{
    private InMemoryLookup $lookup;

    private SandboxedTwigRenderer $renderer;

    protected function setUp(): void
    {
        $this->lookup = new InMemoryLookup();
        $this->renderer = new SandboxedTwigRenderer(new FeedTemplateSecurityPolicy(), $this->lookup);
    }

    /**
     * @test
     */
    public function it_renders_a_simple_template_with_an_allowed_filter(): void
    {
        self::assertSame('HI', $this->renderer->render('{{ value|upper }}', ['value' => 'hi']));
    }

    /**
     * @test
     */
    public function it_does_not_escape_output(): void
    {
        self::assertSame('a & b', $this->renderer->render('{{ value }}', ['value' => 'a & b']));
    }

    /**
     * @test
     */
    public function it_makes_the_lookup_function_available(): void
    {
        $this->lookup->addTable('badges', ['SKU-1' => ['suffix' => 'Bestseller']]);

        self::assertSame(
            'Bestseller',
            $this->renderer->render('{{ lookup("badges", "SKU-1", "suffix") }}', []),
        );
    }

    /**
     * @test
     */
    public function it_allows_calling_a_getter_on_a_provided_object(): void
    {
        $entity = new class() {
            public function getName(): string
            {
                return 'Acme';
            }
        };

        self::assertSame('Acme', $this->renderer->render('{{ entity.getName() }}', ['entity' => $entity]));
        self::assertSame('Acme', $this->renderer->render('{{ entity.name }}', ['entity' => $entity]));
    }

    /**
     * @test
     */
    public function it_throws_when_the_template_calls_a_disallowed_method(): void
    {
        $entity = new class() {
            public function wipe(): string
            {
                return 'x';
            }
        };

        $this->expectException(SecurityError::class);

        $this->renderer->render('{{ entity.wipe() }}', ['entity' => $entity]);
    }

    /**
     * @test
     */
    public function it_caches_compiled_templates_by_source_and_still_uses_fresh_variables(): void
    {
        self::assertSame('HI', $this->renderer->render('{{ value|upper }}', ['value' => 'hi']));
        self::assertSame('BYE', $this->renderer->render('{{ value|upper }}', ['value' => 'bye']));
    }
}
