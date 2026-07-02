<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\Validator;

use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetApplicatorInterface;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Proves the FeedResolvable constraint is actually wired up end to end: the
 * src/Resources/config/validation/Feed.xml mapping is loaded by the container and the constraint
 * runs in the "sylius" validation group used when the admin enables a feed (§7, §10).
 */
final class FeedResolvableTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_blocks_enabling_a_feed_with_unresolved_fields_and_unblocks_once_they_are_resolved(): void
    {
        $applicator = self::getContainer()->get(MappingPresetApplicatorInterface::class);
        self::assertInstanceOf(MappingPresetApplicatorInterface::class, $applicator);

        $presetRegistry = self::getContainer()->get(MappingPresetRegistryInterface::class);
        self::assertInstanceOf(MappingPresetRegistryInterface::class, $presetRegistry);

        $validator = self::getContainer()->get('validator');
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        $feed = new Feed();
        $feed->setCode('google');
        $applicator->apply($feed, $presetRegistry->get('google_shopping'));
        $feed->enable();

        $violations = $validator->validate($feed, null, ['sylius']);

        self::assertGreaterThan(0, $violations->count(), 'Enabling a feed with unresolved fields should be blocked by the "sylius" validation group');

        $paths = [];
        $messageTemplates = [];
        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
            $messageTemplates[] = $violation->getMessageTemplate();
        }
        self::assertContains('enabled', $paths);
        self::assertContains('setono_sylius_feed.feed.resolve_required_fields_before_enabling', $messageTemplates);

        foreach ($feed->getSources() as $source) {
            foreach ($source->getFields() as $field) {
                $field->setRequiresInput(false);
            }
        }

        $violationsAfterResolving = $validator->validate($feed, null, ['sylius']);

        self::assertCount(0, $violationsAfterResolving, 'Resolving every field should clear the FeedResolvable violation');
    }
}
