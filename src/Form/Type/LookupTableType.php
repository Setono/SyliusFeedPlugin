<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\Model\LookupTableInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Webmozart\Assert\Assert;

/**
 * A LookupTable (§10.1): its code/name, the source (`csv`/`url`) + its single location, the
 * key/join columns and the refresh policy. The location is an unmapped field packed into
 * `sourceConfig` as `path` (csv) or `url` (url) on submit, and unpacked on edit.
 */
final class LookupTableType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'sylius.ui.code',
            ])
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'setono_sylius_feed.form.lookup_table.name',
            ])
            ->add('sourceType', ChoiceType::class, [
                'label' => 'setono_sylius_feed.form.lookup_table.source_type',
                'choices' => [
                    'setono_sylius_feed.form.lookup_table.source.csv' => LookupTableInterface::SOURCE_TYPE_CSV,
                    'setono_sylius_feed.form.lookup_table.source.url' => LookupTableInterface::SOURCE_TYPE_URL,
                ],
            ])
            ->add('location', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'setono_sylius_feed.form.lookup_table.location',
                'help' => 'setono_sylius_feed.form.lookup_table.location_help',
            ])
            ->add('keyColumn', TextType::class, [
                'label' => 'setono_sylius_feed.form.lookup_table.key_column',
            ])
            ->add('joinField', TextType::class, [
                'label' => 'setono_sylius_feed.form.lookup_table.join_field',
            ])
            ->add('refreshPolicy', ChoiceType::class, [
                'label' => 'setono_sylius_feed.form.lookup_table.refresh_policy',
                'choices' => [
                    'setono_sylius_feed.form.lookup_table.policy.manual' => LookupTableInterface::REFRESH_POLICY_MANUAL,
                    'setono_sylius_feed.form.lookup_table.policy.before_generate' => LookupTableInterface::REFRESH_POLICY_BEFORE_GENERATE,
                    'setono_sylius_feed.form.lookup_table.policy.scheduled' => LookupTableInterface::REFRESH_POLICY_SCHEDULED,
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, static function (FormEvent $event): void {
            $table = $event->getData();
            if (!$table instanceof LookupTableInterface) {
                return;
            }

            $config = $table->getSourceConfig();
            $location = $config['url'] ?? $config['path'] ?? null;
            if (is_string($location)) {
                $event->getForm()->get('location')->setData($location);
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $table = $event->getData();
            Assert::isInstanceOf($table, LookupTableInterface::class);

            $location = $event->getForm()->get('location')->getData();
            if (is_string($location) && '' !== $location) {
                $key = LookupTableInterface::SOURCE_TYPE_URL === $table->getSourceType() ? 'url' : 'path';
                $table->setSourceConfig([$key => $location]);
            }
        });
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_feed_lookup_table';
    }
}
