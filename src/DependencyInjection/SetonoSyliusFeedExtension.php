<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection;

use Setono\SyliusFeedPlugin\DataProvider\DataProviderInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusFeedExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_feed.storage.feed', $config['storage']['feed']);
        $container->setParameter('setono_sylius_feed.storage.feed_tmp', $config['storage']['feed_tmp']);

        $container->registerForAutoconfiguration(DataProviderInterface::class)->addTag('setono_sylius_feed.data_provider');
        $container->registerForAutoconfiguration(FeedTypeInterface::class)->addTag('setono_sylius_feed.feed_type');

        $loader->load('services.xml');

        $this->registerResources('setono_sylius_feed', $config['driver'], $config['resources'], $container);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('framework')) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'workflows' => [
                FeedGraph::GRAPH => [
                    'type' => 'state_machine',
                    'marking_store' => [
                        'type' => 'method',
                        'property' => 'state',
                    ],
                    'supports' => [FeedInterface::class],
                    'places' => FeedGraph::getStates(),
                    'transitions' => [
                        FeedGraph::TRANSITION_PROCESS => [
                            'from' => [FeedGraph::STATE_UNPROCESSED, FeedGraph::STATE_READY, FeedGraph::STATE_ERROR],
                            'to' => FeedGraph::STATE_PROCESSING,
                        ],
                        FeedGraph::TRANSITION_ERRORED => [
                            'from' => FeedGraph::STATE_PROCESSING,
                            'to' => FeedGraph::STATE_ERROR,
                        ],
                        FeedGraph::TRANSITION_PROCESSED => [
                            'from' => FeedGraph::STATE_PROCESSING,
                            'to' => FeedGraph::STATE_READY,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
