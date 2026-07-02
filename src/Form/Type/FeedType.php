<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetApplicatorInterface;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Webmozart\Assert\Assert;

final class FeedType extends AbstractResourceType
{
    /**
     * @param array<string> $validationGroups
     */
    public function __construct(
        string $dataClass,
        private readonly FormatRegistryInterface $formatRegistry,
        private readonly MappingPresetRegistryInterface $mappingPresetRegistry,
        private readonly MappingPresetApplicatorInterface $mappingPresetApplicator,
        array $validationGroups = [],
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'sylius.ui.code',
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => FeedTranslationType::class,
                'label' => 'setono_sylius_feed.form.feed.name',
            ])
            ->add('channels', ChannelChoiceType::class, [
                'multiple' => true,
                'label' => 'sylius.ui.channels',
            ])
            ->add('target', ChoiceType::class, [
                'label' => 'setono_sylius_feed.form.feed.target',
                'help' => 'setono_sylius_feed.form.feed.target_help',
                'required' => false,
                'mapped' => false,
                'placeholder' => 'setono_sylius_feed.form.feed.target_placeholder',
                'choices' => $this->targetChoices(),
            ])
            ->add('format', ChoiceType::class, [
                'choices' => $this->formatChoices(),
                'label' => 'setono_sylius_feed.form.feed.format',
                'required' => false,
                'placeholder' => 'setono_sylius_feed.form.feed.format_placeholder',
            ])
            ->add('enabled', CheckboxType::class, [
                'required' => false,
                'label' => 'sylius.ui.enabled',
            ])
            ->add('sources', CollectionType::class, [
                'label' => 'setono_sylius_feed.form.feed.sources',
                'entry_type' => FeedSourceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('deliveryTargets', CollectionType::class, [
                'label' => 'setono_sylius_feed.form.feed.delivery_targets',
                'entry_type' => DeliveryTargetType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
        ;

        // Seed the feed from the chosen target before positions are reindexed (higher priority).
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $feed = $event->getData();
            Assert::isInstanceOf($feed, FeedInterface::class);

            $target = $event->getForm()->get('target')->getData();
            if (!is_string($target) || '' === $target || !$feed->getSources()->isEmpty()) {
                return;
            }

            if ($this->mappingPresetRegistry->has($target)) {
                $this->mappingPresetApplicator->apply($feed, $this->mappingPresetRegistry->get($target));
            }
        }, 10);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $feed = $event->getData();
            Assert::isInstanceOf($feed, FeedInterface::class);

            $position = 0;
            foreach ($feed->getSources() as $source) {
                $source->setPosition($position);
                ++$position;
            }

            $position = 0;
            foreach ($feed->getDeliveryTargets() as $deliveryTarget) {
                $deliveryTarget->setPosition($position);
                ++$position;
            }
        });
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_feed_feed';
    }

    /**
     * @return array<string, string>
     */
    private function formatChoices(): array
    {
        $choices = [];
        foreach (array_keys($this->formatRegistry->all()) as $code) {
            $choices[$code] = $code;
        }

        return $choices;
    }

    /**
     * The mapping presets the admin can seed a feed from (§7 "target" picker).
     *
     * @return array<string, string>
     */
    private function targetChoices(): array
    {
        $choices = [];
        foreach ($this->mappingPresetRegistry->all() as $preset) {
            $choices[$preset->getLabel()] = $preset->getCode();
        }

        return $choices;
    }
}
