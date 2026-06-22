<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\Mapping\SourceType;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One output-field mapping (§4.1). M3 supports the `field` and `literal` source types; the
 * `expression`/`twig` sources and the transformation/condition sub-editors land in M4.
 */
final class FeedFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('outputField', TextType::class, [
                'label' => 'setono_sylius_feed.form.feed_field.output_field',
            ])
            ->add('sourceType', ChoiceType::class, [
                'label' => 'setono_sylius_feed.form.feed_field.source_type',
                'choices' => [
                    'setono_sylius_feed.form.feed_field.source.field' => SourceType::FIELD->value,
                    'setono_sylius_feed.form.feed_field.source.literal' => SourceType::LITERAL->value,
                ],
            ])
            ->add('sourceValue', TextType::class, [
                'required' => false,
                'label' => 'setono_sylius_feed.form.feed_field.source_value',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', FeedField::class);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_feed_feed_field';
    }
}
