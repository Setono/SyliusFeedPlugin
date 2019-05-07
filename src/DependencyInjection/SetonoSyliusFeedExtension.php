<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection;

use Setono\SyliusFeedPlugin\Template\Context\GoogleShoppingTemplateContext;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusFeedExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_feed.dir', $config['dir']);
        $container->setParameter('setono_sylius_feed.messenger.transport', $config['messenger']['transport']);
        $container->setParameter('setono_sylius_feed.messenger.command_bus', $config['messenger']['command_bus']);
        $container->setParameter('setono_sylius_feed.templates', $config['templates']);

        $loader->load('services.xml');

        $this->registerResources('setono_sylius_feed', $config['driver'], $config['resources'], $container);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('setono_sylius_feed')) {
            return;
        }

        $templates = [];
        $templates[] = [
            'type' => 'setono_sylius_feed_google_shopping',
            'context' => GoogleShoppingTemplateContext::class,
            'path' => '@SetonoSyliusFeedPlugin/Template/google_shopping.xml.twig',
            'label' => 'setono_sylius_feed.template.google_shopping',
        ];

        $container->prependExtensionConfig('setono_sylius_feed', [
           'templates' => $templates,
        ]);
    }
}
