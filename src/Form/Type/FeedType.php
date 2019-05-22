<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\Type;

use Setono\SyliusFeedPlugin\Template\Registry\TemplateRegistryInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FeedType extends AbstractResourceType
{
    /**
     * @var TemplateRegistryInterface
     */
    private $templateRegistry;

    public function __construct(string $dataClass, TemplateRegistryInterface $templateRegistry, array $validationGroups = [])
    {
        parent::__construct($dataClass, $validationGroups);

        $this->templateRegistry = $templateRegistry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'setono_sylius_feed.form.feed.name',
            ])
            ->add('slug', TextType::class, [
                'label' => 'setono_sylius_feed.form.feed.slug',
            ])
            ->add('template', ChoiceType::class, [
                'placeholder' => 'setono_sylius_feed.form.feed.template_placeholder',
                'label' => 'setono_sylius_feed.form.feed.template',
                'choices' => $this->templateRegistry->all(),
                'choice_label' => 'label',
                'choice_value' => 'type'
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
