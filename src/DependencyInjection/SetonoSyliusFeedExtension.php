<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection;

use Setono\SyliusFeedPlugin\Specification\Registry\SpecificationRegistryInterface;
use Setono\SyliusFeedPlugin\Specification\Specification;
use Setono\SyliusFeedPlugin\Specification\Vendor\Google\Shopping\Product;
use Setono\SyliusFeedPlugin\Workflow\FeedUpdateBatchWorkflow;
use Setono\SyliusFeedPlugin\Workflow\FeedUpdateWorkflow;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusFeedExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         *
         * @var array{resources: array, specifications: list<class-string<Specification>>} $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.xml');

        self::registerSpecifications($container, $config['specifications']);

        $this->registerResources('setono_sylius_feed', SyliusResourceBundle::DRIVER_DOCTRINE_ORM, $config['resources'], $container);
    }

    /**
     * @param list<class-string<Specification>> $specifications
     */
    private static function registerSpecifications(ContainerBuilder $container, array $specifications): void
    {
        $specifications[] = Product::class;
        $specifications = array_unique($specifications);

        $registry = $container->findDefinition(SpecificationRegistryInterface::class);
        foreach ($specifications as $specification) {
            $registry->addMethodCall('add', [$specification]);
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        // todo this storage needs to be configurable for the plugin somehow
        $container->prependExtensionConfig('flysystem', [
            'storages' => [
                'setono_sylius_feed.storage' => [
                    'adapter' => 'local',
                    'options' => [
                        'directory' => '%kernel.project_dir%/var/storage/feeds',
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'buses' => [
                    'setono_sylius_feed.command_bus' => [
                        'middleware' => [
                            'doctrine_ping_connection',
                        ],
                    ],
                ],
            ],
            'workflows' => FeedUpdateWorkflow::getConfig() + FeedUpdateBatchWorkflow::getConfig(),
        ]);

        $container->prependExtensionConfig('sylius_grid', [
            'grids' => [
                'setono_sylius_feed_admin_feed' => [
                    'driver' => [
                        'name' => SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
                        'options' => [
                            'class' => '%setono_sylius_feed.model.feed.class%',
                        ],
                    ],
                    'fields' => [
                        'name' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_feed.ui.name',
                        ],
                        'slug' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_feed.ui.slug',
                        ],
                        'specification' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_feed.ui.specification',
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => [
                                'type' => 'create',
                            ],
                        ],
                        'item' => [
                            'update' => [
                                'type' => 'update',
                            ],
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
