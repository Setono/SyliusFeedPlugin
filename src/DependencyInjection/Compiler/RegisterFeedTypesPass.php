<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterFeedTypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('setono_sylius_feed.registry.feed_type')) {
            return;
        }

        $registry = $container->getDefinition('setono_sylius_feed.registry.feed_type');

        /**
         * @var string $id
         */
        foreach (array_keys($container->findTaggedServiceIds('setono_sylius_feed.feed_type')) as $id) {
            $registry->addArgument(new Reference($id));
        }
    }
}
