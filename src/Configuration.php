<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL;

use Kiboko\Contract\Configurator\PluginConfigurationInterface;
use Kiboko\Plugin\SQL;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Configuration implements PluginConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('sql');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->ifTrue(fn (array $value) => \array_key_exists('extractor', $value) && \array_key_exists('loader', $value))
                ->thenInvalid('Your configuration should either contain the "extractor" or the "loader" key, not both.')
            ->end()
            ->validate()
                ->ifTrue(fn (array $value) => \array_key_exists('extractor', $value) && \array_key_exists('lookup', $value))
                ->thenInvalid('Your configuration should either contain the "extractor" or the "lookup" key, not both.')
            ->end()
            ->validate()
                ->ifTrue(fn (array $value) => \array_key_exists('loader', $value) && \array_key_exists('lookup', $value))
                ->thenInvalid('Your configuration should either contain the "loader" or the "lookup" key, not both.')
            ->end()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('connection')
                    ->children()
                        ->scalarNode('dsn')
                            ->isRequired()
                            ->validate()
                                ->ifTrue(isExpression())
                                ->then(asExpression())
                            ->end()
                        ->end()
                        ->scalarNode('username')
                            ->validate()
                                ->ifTrue(isExpression())
                                ->then(asExpression())
                            ->end()
                        ->end()
                        ->scalarNode('password')
                            ->validate()
                                ->ifTrue(isExpression())
                                ->then(asExpression())
                            ->end()
                        ->end()
                        ->booleanNode('shared')->end()
                        ->append((new SQL\Configuration\Connection\Options())->getConfigTreeBuilder()->getRootNode())
                    ->end()
                ->end()
                ->arrayNode('before')
                    ->children()
                        ->arrayNode('queries')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('after')
                    ->children()
                        ->arrayNode('queries')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->append(node: (new Configuration\Extractor())->getConfigTreeBuilder()->getRootNode())
                ->append(node: (new Configuration\Lookup())->getConfigTreeBuilder()->getRootNode())
                ->append(node: (new Configuration\Loader())->getConfigTreeBuilder()->getRootNode())
            ->end()
        ;

        return $builder;
    }
}
