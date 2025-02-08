<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\CurrencyBundle\Form\Type\CurrencyChoiceType;
use Sylius\Bundle\LocaleBundle\Form\Type\LocaleChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\FormBuilderInterface;

final class FeedScopeType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('channel', ChannelChoiceType::class, [
                'label' => 'sylius.ui.channel',
            ])
            ->add('localeCode', LocaleChoiceType::class, [
                'label' => 'sylius.ui.locale',
            ])
            ->add('currencyCode', CurrencyChoiceType::class, [
                'label' => 'sylius.ui.currency',
            ])
        ;
    }
}
