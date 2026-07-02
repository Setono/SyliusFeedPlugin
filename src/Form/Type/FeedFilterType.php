<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\Model\FeedFilter;
use Setono\SyliusFeedPlugin\Model\FeedFilterInterface;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One include/exclude filter row (§11): the `field` to test, the `operator` from the shared
 * vocabulary, the comparison `value` (a reference or quoted literal), the `action`
 * (include/exclude) and the `stage` (pre/post) at which it runs.
 */
final class FeedFilterType extends AbstractType
{
    public function __construct(private readonly OperatorRegistryInterface $operatorRegistry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('field', TextType::class, [
                'label' => 'setono_sylius_feed.form.feed_filter.field',
            ])
            ->add('operator', ChoiceType::class, [
                'label' => 'setono_sylius_feed.form.feed_filter.operator',
                'choices' => $this->operatorChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('value', TextType::class, [
                'required' => false,
                'label' => 'setono_sylius_feed.form.feed_filter.value',
                'help' => 'setono_sylius_feed.form.feed_filter.value_help',
            ])
            ->add('action', ChoiceType::class, [
                'label' => 'setono_sylius_feed.form.feed_filter.action',
                'choices' => [
                    'setono_sylius_feed.form.feed_filter.action_choices.include' => FeedFilterInterface::ACTION_INCLUDE,
                    'setono_sylius_feed.form.feed_filter.action_choices.exclude' => FeedFilterInterface::ACTION_EXCLUDE,
                ],
            ])
            ->add('stage', ChoiceType::class, [
                'label' => 'setono_sylius_feed.form.feed_filter.stage',
                'choices' => [
                    'setono_sylius_feed.form.feed_filter.stage_choices.pre' => FeedFilterInterface::STAGE_PRE,
                    'setono_sylius_feed.form.feed_filter.stage_choices.post' => FeedFilterInterface::STAGE_POST,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', FeedFilter::class);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_feed_feed_filter';
    }

    /**
     * @return array<string, string>
     */
    private function operatorChoices(): array
    {
        $choices = [];
        foreach ($this->operatorRegistry->all() as $operator) {
            $name = $operator->getName();
            $choices[$name] = $name;
        }

        ksort($choices);

        return $choices;
    }
}
