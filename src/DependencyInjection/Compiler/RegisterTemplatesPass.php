<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection\Compiler;

use Setono\SyliusFeedPlugin\Template\Template;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterTemplatesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('setono_sylius_feed.templates')) {
            return;
        }

        if (!$container->has('setono_sylius_feed.registry.template')) {
            return;
        }

        $templates = $container->getParameter('setono_sylius_feed.templates');
        $templateRegistry = $container->getDefinition('setono_sylius_feed.registry.template');

        foreach ($templates as $template) {
            $templateServiceId = 'setono_sylius_feed.template.' . $template['type'];
            $container->setDefinition($templateServiceId, new Definition(Template::class, [
                $template['type'], $template['context'], $template['path'], $template['label'],
            ]));

            $templateRegistry->addArgument(new Reference($templateServiceId));
        }
    }
}
