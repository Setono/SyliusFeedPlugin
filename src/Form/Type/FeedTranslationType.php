<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FeedTranslationType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'setono_sylius_feed.form.feed.name',
            ])
            ->add('slug', TextType::class, [
                'required' => false,
                'label' => 'setono_sylius_feed.form.feed.slug',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_feed_feed_translation';
    }
}
