<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Form\DataTransformer\FeedTypeToCodeTransformer;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedTypeChoiceType extends AbstractType
{
    private FeedTypeRegistryInterface $feedTypeRegistry;

    public function __construct(FeedTypeRegistryInterface $feedTypeRegistry)
    {
        $this->feedTypeRegistry = $feedTypeRegistry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new FeedTypeToCodeTransformer($this->feedTypeRegistry));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'placeholder' => 'setono_sylius_feed.form.feed.feed_type_placeholder',
                'label' => 'setono_sylius_feed.form.feed.feed_type',
                'choices' => $this->feedTypeRegistry->all(),
                'choice_label' => static function (FeedTypeInterface $choice): string {
                    return 'setono_sylius_feed.feed_type.' . $choice->getCode();
                },
                'choice_value' => 'code',
            ])
        ;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_feed_feed_type_choice';
    }
}
