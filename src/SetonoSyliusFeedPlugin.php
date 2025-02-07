<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin;

use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusFeedPlugin\Serializer\CompositeSpecificationSerializer;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusFeedPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CompositeCompilerPass(
            CompositeSpecificationSerializer::class,
            'setono_sylius_feed.specification_serializer',
        ));
    }

    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }
}
