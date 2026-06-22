<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\Form;

use Setono\SyliusFeedPlugin\Form\Type\FeedTranslationType;
use Setono\SyliusFeedPlugin\Form\Type\FeedType;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The feed form mixes Sylius resource types (channels, translations), so it is exercised through
 * the real form factory rather than a bare TypeTestCase.
 */
final class FeedTypeTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_builds_the_feed_form_with_registry_backed_format_choices(): void
    {
        $factory = self::getContainer()->get('form.factory');
        self::assertInstanceOf(FormFactoryInterface::class, $factory);

        $form = $factory->create(FeedType::class, new Feed());

        foreach (['code', 'translations', 'channels', 'format', 'enabled'] as $field) {
            self::assertTrue($form->has($field), sprintf('The form is missing the "%s" field', $field));
        }

        /** @var array<string, string> $formatChoices */
        $formatChoices = $form->get('format')->getConfig()->getOption('choices');
        self::assertContains('google_rss', $formatChoices, 'The format choices should come from the FormatRegistry');
    }

    /**
     * @test
     */
    public function it_builds_the_translation_form(): void
    {
        $factory = self::getContainer()->get('form.factory');
        self::assertInstanceOf(FormFactoryInterface::class, $factory);

        $form = $factory->create(FeedTranslationType::class);

        self::assertTrue($form->has('name'));
        self::assertTrue($form->has('slug'));
    }
}
