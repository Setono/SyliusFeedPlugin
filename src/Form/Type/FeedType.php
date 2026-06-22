<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FeedType extends AbstractResourceType
{
    /**
     * @param array<string> $validationGroups
     */
    public function __construct(
        string $dataClass,
        private readonly FormatRegistryInterface $formatRegistry,
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
            ->add('format', ChoiceType::class, [
                'choices' => $this->formatChoices(),
                'label' => 'setono_sylius_feed.form.feed.format',
            ])
            ->add('enabled', CheckboxType::class, [
                'required' => false,
                'label' => 'sylius.ui.enabled',
            ])
        ;
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
}
