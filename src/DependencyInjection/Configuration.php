<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DependencyInjection;

use Setono\SyliusFeedPlugin\Doctrine\ORM\FeedRepository;
use Setono\SyliusFeedPlugin\Form\Type\FeedType;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Template\Context\TemplateContextInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Form\Type\DefaultResourceType;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('setono_sylius_feed');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('setono_sylius_feed');
        }

        $rootNode
            ->children()
                ->scalarNode('driver')->defaultValue(SyliusResourceBundle::DRIVER_DOCTRINE_ORM)->end()
                ->scalarNode('dir')
                    ->info('The directory where feeds should be saved')
                    ->defaultValue('%kernel.project_dir%/var/feed')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('messenger')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('transport')
                            ->cannotBeEmpty()
                            ->defaultNull()
                            ->example('amqp')
                            ->info('The Messenger transport to use')
                        ->end()
                        ->scalarNode('command_bus')
                            ->cannotBeEmpty()
                            ->defaultValue('message_bus')
                            ->example('message_bus')
                            ->info('The service id for your command bus')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('templates')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('type')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('A unique string representing this template')
                                ->example('setono_sylius_feed_google_shopping')
                            ->end()
                            ->scalarNode('context')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('The context class for this template. Must implement ' . TemplateContextInterface::class)
                            ->end()
                            ->scalarNode('path')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('The path to the twig template')
                            ->end()
                            ->scalarNode('label')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('The label to use in user interfaces')
                                ->example('setono_sylius_feed.template.google_shopping')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('feed')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(Feed::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(FeedInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(FeedRepository::class)->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                        ->scalarNode('form')->defaultValue(FeedType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
