<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Scripting;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Scripting\FeedTemplateSecurityPolicy;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedMethodError;

final class FeedTemplateSecurityPolicyTest extends TestCase
{
    private FeedTemplateSecurityPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new FeedTemplateSecurityPolicy();
    }

    private function stub(): object
    {
        return new class() {
            public function getName(): string
            {
                return 'Acme';
            }

            public function isActive(): bool
            {
                return true;
            }

            public function hasStock(): bool
            {
                return true;
            }

            public function __toString(): string
            {
                return 'stub';
            }

            public function delete(): void
            {
            }
        };
    }

    /**
     * @test
     */
    public function it_allows_getter_methods(): void
    {
        $this->expectNotToPerformAssertions();

        $this->policy->checkMethodAllowed($this->stub(), 'getName');
    }

    /**
     * @test
     */
    public function it_allows_is_prefixed_methods(): void
    {
        $this->expectNotToPerformAssertions();

        $this->policy->checkMethodAllowed($this->stub(), 'isActive');
    }

    /**
     * @test
     */
    public function it_allows_has_prefixed_methods(): void
    {
        $this->expectNotToPerformAssertions();

        $this->policy->checkMethodAllowed($this->stub(), 'hasStock');
    }

    /**
     * @test
     */
    public function it_allows_to_string(): void
    {
        $this->expectNotToPerformAssertions();

        $this->policy->checkMethodAllowed($this->stub(), '__toString');
    }

    /**
     * @test
     */
    public function it_allows_accessor_methods_regardless_of_case(): void
    {
        $this->expectNotToPerformAssertions();

        $this->policy->checkMethodAllowed($this->stub(), 'GETNAME');
        $this->policy->checkMethodAllowed($this->stub(), 'IsActive');
    }

    /**
     * @test
     */
    public function it_throws_for_a_non_accessor_method(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);

        $this->policy->checkMethodAllowed($this->stub(), 'delete');
    }

    /**
     * @test
     */
    public function it_always_allows_reading_a_property(): void
    {
        $this->expectNotToPerformAssertions();

        $this->policy->checkPropertyAllowed($this->stub(), 'anything');
    }

    /**
     * @test
     */
    public function it_allows_an_allowlisted_combination_of_tags_filters_and_functions(): void
    {
        $this->expectNotToPerformAssertions();

        $this->policy->checkSecurity(['if'], ['upper'], ['lookup']);
    }

    /**
     * @test
     */
    public function it_throws_for_a_disallowed_filter(): void
    {
        $this->expectException(SecurityError::class);

        $this->policy->checkSecurity([], ['url_decode'], []);
    }

    /**
     * @test
     */
    public function it_throws_for_a_disallowed_tag(): void
    {
        $this->expectException(SecurityError::class);

        $this->policy->checkSecurity(['include'], [], []);
    }

    /**
     * @test
     */
    public function it_throws_for_a_disallowed_function(): void
    {
        $this->expectException(SecurityError::class);

        $this->policy->checkSecurity([], [], ['constant']);
    }
}
