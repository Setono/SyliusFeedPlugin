<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin;

use Setono\SyliusFeedPlugin\DependencyInjection\Compiler\RegisterFeedTypesPass;
use Setono\SyliusFeedPlugin\DependencyInjection\Compiler\RegisterFilesystemPass;
use Setono\SyliusFeedPlugin\DependencyInjection\Compiler\ValidateDataProvidersPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusFeedPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterFeedTypesPass());
        $container->addCompilerPass(new RegisterFilesystemPass());
        $container->addCompilerPass(new ValidateDataProvidersPass());
    }
}
