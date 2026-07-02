<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * A feed source: which `feedType` to iterate and its `FeedField` mapping editor (§13). The field
 * positions are reindexed on submit so their order in the UI is preserved.
 */
final class FeedSourceType extends AbstractType
{
    public function __construct(private readonly FeedTypeRegistryInterface $feedTypeRegistry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('feedType', ChoiceType::class, [
                'label' => 'setono_sylius_feed.form.feed_source.feed_type',
                'choices' => $this->feedTypeChoices(),
            ])
            ->add('fields', CollectionType::class, [
                'label' => 'setono_sylius_feed.form.feed_source.fields',
                'entry_type' => FeedFieldType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $source = $event->getData();
            Assert::isInstanceOf($source, FeedSourceInterface::class);

            $position = 0;
            foreach ($source->getFields() as $field) {
                $field->setPosition($position);
                ++$position;
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', FeedSource::class);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_feed_feed_source';
    }

    /**
     * @return array<string, string>
     */
    private function feedTypeChoices(): array
    {
        $choices = [];
        foreach ($this->feedTypeRegistry->all() as $feedType) {
            $choices[$feedType->getLabel()] = $feedType->getCode();
        }

        return $choices;
    }
}
