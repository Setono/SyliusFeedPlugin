<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FeedType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'setono_sylius_feed.form.feed.name',
            ])
            ->add('slug', TextType::class, [
                'label' => 'setono_sylius_feed.form.feed.slug',
            ])
            ->add('specification', SpecificationChoiceType::class, [
                'label' => 'setono_sylius_feed.form.feed.specification',
            ])
            ->add('entities', EntityChoiceType::class, [
                'label' => 'setono_sylius_feed.form.feed.entities',
                'multiple' => true,
            ])
        ;
    }
}
