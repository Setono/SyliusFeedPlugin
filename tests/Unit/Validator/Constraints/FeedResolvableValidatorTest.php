<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Validator\Constraints;

use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Validator\Constraints\FeedResolvable;
use Setono\SyliusFeedPlugin\Validator\Constraints\FeedResolvableValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class FeedResolvableValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): FeedResolvableValidator
    {
        return new FeedResolvableValidator();
    }

    /**
     * @test
     */
    public function it_builds_a_violation_when_an_enabled_feed_has_a_field_requiring_input(): void
    {
        $feed = $this->feedWithField(true);
        $feed->enable();

        $constraint = new FeedResolvable();
        $this->validator->validate($feed, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.enabled')
            ->assertRaised();
    }

    /**
     * @test
     */
    public function it_raises_no_violation_when_an_enabled_feed_has_every_field_resolved(): void
    {
        $feed = $this->feedWithField(false);
        $feed->enable();

        $this->validator->validate($feed, new FeedResolvable());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_raises_no_violation_when_a_disabled_feed_has_a_field_requiring_input(): void
    {
        $feed = $this->feedWithField(true);
        $feed->disable();

        $this->validator->validate($feed, new FeedResolvable());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_raises_no_violation_for_a_null_value(): void
    {
        $this->validator->validate(null, new FeedResolvable());

        $this->assertNoViolation();
    }

    private function feedWithField(bool $requiresInput): Feed
    {
        $field = new FeedField();
        $field->setOutputField('g:brand');
        $field->setRequiresInput($requiresInput);

        $source = new FeedSource();
        $source->addField($field);

        $feed = new Feed();
        $feed->addSource($source);

        return $feed;
    }
}
