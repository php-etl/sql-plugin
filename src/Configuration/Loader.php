<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Loader implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('loader');

        $builder->getRootNode()
            ->children()
                ->scalarNode('query')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(fn ($data) => is_string($data) && $data !== '' && (!str_starts_with(strtoupper($data), 'INSERT') && !str_starts_with(strtoupper($data), 'UPDATE')))
                        ->thenInvalid('Your query must start with the keyword "INSERT" ou "UPDATE".')
                    ->end()
                ->end()
                ->arrayNode('params')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('key')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifTrue(fn ($data) => !is_string($data) && !is_int($data))
                                    ->thenInvalid('The key of your parameter must be of type string or int.')
                                ->end()
                                 ->validate()
                                    ->ifTrue(fn ($data) => is_int($data) && $data < 1)
                                    ->thenInvalid('The key of your parameter cannot be lower than 1.')
                                ->end()
                                ->validate()
                                    ->ifTrue(isExpression())
                                    ->then(asExpression())
                                ->end()
                            ->end()
                            ->scalarNode('value')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifTrue(isExpression())
                                    ->then(asExpression())
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('connection')
                    ->children()
                        ->scalarNode('dsn')
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
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
