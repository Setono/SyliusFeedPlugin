<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form;

use Setono\SyliusFeedPlugin\Specification\Registry\SpecificationRegistryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SpecificationChoiceType extends AbstractType
{
    public function __construct(private readonly SpecificationRegistryInterface $specificationRegistry)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->specificationRegistry->all(),
            'choice_label' => fn (string $specification) => $specification,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
