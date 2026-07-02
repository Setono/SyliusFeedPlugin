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
    public function it_maps_sources_and_fields_on_submit(): void
    {
        $factory = self::getContainer()->get('form.factory');
        self::assertInstanceOf(FormFactoryInterface::class, $factory);

        $form = $factory->create(FeedType::class, new Feed(), ['csrf_protection' => false]);
        $form->submit([
            'code' => 'google',
            'format' => 'google_rss',
            'enabled' => true,
            'sources' => [
                [
                    'feedType' => 'product_variant',
                    'fields' => [
                        ['outputField' => 'g:id', 'sourceType' => 'field', 'sourceValue' => 'id'],
                        ['outputField' => 'g:title', 'sourceType' => 'field', 'sourceValue' => 'title'],
                    ],
                ],
            ],
        ]);

        self::assertTrue($form->isSynchronized());

        $feed = $form->getData();
        self::assertInstanceOf(Feed::class, $feed);

        $sources = $feed->getSources()->getValues();
        self::assertCount(1, $sources);
        $source = $sources[0];
        self::assertSame('product_variant', $source->getFeedType());
        self::assertSame(0, $source->getPosition());

        $fields = $source->getFields()->getValues();
        self::assertCount(2, $fields);
        self::assertSame('g:id', $fields[0]->getOutputField());
        self::assertSame(0, $fields[0]->getPosition());
        self::assertSame('g:title', $fields[1]->getOutputField());
        self::assertSame(1, $fields[1]->getPosition());

        // rendering the view walks the submitted source/field entries (covers their block prefixes)
        $view = $form->createView();
        self::assertArrayHasKey('sources', $view->children);
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
