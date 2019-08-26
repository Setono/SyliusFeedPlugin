<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection;

use Exception;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusFeedExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    /**
     * @throws Exception
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

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
