<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\Mapping\SourceType;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One output-field mapping (§4.1): the output key, the source (`field`/`literal`/`expression`/
 * `twig`) and its value, plus the `requiresInput` flag a preset sets on rows the admin must
 * complete before the feed can be enabled.
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
                    'setono_sylius_feed.form.feed_field.source.expression' => SourceType::EXPRESSION->value,
                    'setono_sylius_feed.form.feed_field.source.twig' => SourceType::TWIG->value,
                ],
            ])
            ->add('sourceValue', TextType::class, [
                'required' => false,
                'label' => 'setono_sylius_feed.form.feed_field.source_value',
            ])
            ->add('requiresInput', CheckboxType::class, [
                'required' => false,
                'label' => 'setono_sylius_feed.form.feed_field.requires_input',
                'help' => 'setono_sylius_feed.form.feed_field.requires_input_help',
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
