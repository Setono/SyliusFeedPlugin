<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Format\FormatInterface;
use Setono\SyliusFeedPlugin\Lookup\LookupSourceInterface;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Operator\OperatorInterface;
use Setono\SyliusFeedPlugin\Publish\GuardrailInterface;
use Setono\SyliusFeedPlugin\Transformation\TransformationInterface;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Setono\SyliusFeedPlugin\Writer\FeedWriterInterface;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusFeedExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    /**
     * Maps each extension-point interface to the tag that collects its implementations into a
     * registry. This is registered for autoconfiguration purely as a developer-experience aid for
     * applications that add their own implementations — the plugin's own services do not rely on it
     * and are tagged explicitly in the service definitions (no autowire/autoconfigure).
     */
    private const AUTOCONFIGURED_TAGS = [
        FeedTypeInterface::class => 'setono_sylius_feed.feed_type',
        MappingPresetInterface::class => 'setono_sylius_feed.mapping_preset',
        ValueResolverInterface::class => 'setono_sylius_feed.value_resolver',
        LookupSourceInterface::class => 'setono_sylius_feed.lookup_source',
        TransformationInterface::class => 'setono_sylius_feed.transformation',
        OperatorInterface::class => 'setono_sylius_feed.operator',
        FeedWriterInterface::class => 'setono_sylius_feed.writer',
        FormatInterface::class => 'setono_sylius_feed.format',
        GuardrailInterface::class => 'setono_sylius_feed.guardrail',
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     driver: string,
         *     storage: array{feed: string, feed_tmp: string},
         *     resources: array<string, mixed>
         * } $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_feed.storage.feed', $config['storage']['feed']);
        $container->setParameter('setono_sylius_feed.storage.feed_tmp', $config['storage']['feed_tmp']);

        foreach (self::AUTOCONFIGURED_TAGS as $interface => $tag) {
            $container->registerForAutoconfiguration($interface)->addTag($tag);
        }

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
                    'initial_marking' => FeedGraph::STATE_READY,
                    'places' => FeedGraph::getStates(),
                    'transitions' => [
                        FeedGraph::TRANSITION_PROCESS => [
                            'from' => [FeedGraph::STATE_READY],
                            'to' => FeedGraph::STATE_PROCESSING,
                        ],
                        FeedGraph::TRANSITION_COMPLETE => [
                            'from' => [FeedGraph::STATE_PROCESSING],
                            'to' => FeedGraph::STATE_COMPLETED,
                        ],
                        FeedGraph::TRANSITION_FAIL => [
                            'from' => [FeedGraph::STATE_PROCESSING],
                            'to' => FeedGraph::STATE_FAILED,
                        ],
                        FeedGraph::TRANSITION_RESET => [
                            'from' => [FeedGraph::STATE_COMPLETED, FeedGraph::STATE_FAILED],
                            'to' => FeedGraph::STATE_READY,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
