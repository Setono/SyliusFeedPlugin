<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EntityChoiceType extends AbstractType
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $entities = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                $entities[] = $metadata->getName();
            }
        }

        $resolver->setDefaults([
            'choices' => $entities,
            'choice_label' => fn (string $entity) => $entity,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
