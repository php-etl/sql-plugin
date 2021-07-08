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
                    ->beforeNormalization()
                        ->ifTrue(function ($data) {
                            foreach ($data as $key => $value) {
                                return !is_string($key) && !is_int($key);
                            }

                            return false;
                        })
                        ->thenInvalid('Your parameter key can only be a string or an integer.')
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(function ($data) {
                            foreach ($data as $key => $value) {
                                return is_string($key) && str_starts_with($key, ':');
                            }

                            return false;
                        })
                        ->thenInvalid('Your parameter can\'t start with ":".')
                    ->end()
                    ->scalarPrototype()
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
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
