<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FeedType extends AbstractResourceType
{
    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    public function __construct(string $dataClass, FeedTypeRegistryInterface $feedTypeRegistry, array $validationGroups = [])
    {
        parent::__construct($dataClass, $validationGroups);

        $this->feedTypeRegistry = $feedTypeRegistry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'setono_sylius_feed.form.feed.name',
            ])
            ->add('uuid', TextType::class, [
                'label' => 'setono_sylius_feed.form.feed.uuid',
                'disabled' => true,
            ])
            ->add('feedType', ChoiceType::class, [
                'placeholder' => 'setono_sylius_feed.form.feed.feed_type_placeholder',
                'label' => 'setono_sylius_feed.form.feed.feed_type',
                'choices' => $this->feedTypeRegistry->all(),
                'choice_label' => static function (FeedTypeInterface $choice, $key, $value) {
                    return 'setono_sylius_feed.feed_type.' . $choice->getCode();
                },
                'choice_value' => 'code',
            ])
            ->add('enabled', CheckboxType::class, [
                'required' => false,
                'label' => 'sylius.ui.enabled',
            ])
            ->add('channels', ChannelChoiceType::class, [
                'multiple' => true,
                'expanded' => true,
                'label' => 'sylius.ui.channels',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_feed_feed';
    }
}
