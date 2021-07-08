<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration;

use Kiboko\Plugin\FastMap;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Lookup implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('lookup');

        $builder->getRootNode()
             ->validate()
                ->ifTrue(fn ($data) => array_key_exists('conditional', $data) && is_array($data['conditional']) && count($data['conditional']) <= 0)
                ->then(function ($data) {
                    unset($data['conditional']);
                    return $data;
                })
            ->end()
            ->children()
                ->scalarNode('query')
                    ->validate()
                        ->ifTrue(fn ($data) => is_string($data) && $data !== '' && (!str_starts_with(strtoupper($data), 'SELECT') && !str_starts_with(strtoupper($data), 'select')))
                        ->thenInvalid('Your query should be start with "SELECT".')
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
                ->append((new FastMap\Configuration('merge'))->getConfigTreeBuilder()->getRootNode())
                ->append($this->getConditionalTreeBuilder()->getRootNode())
            ->end()
        ;

        return $builder;
    }

    private function getConditionalTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('conditional');

        $builder->getRootNode()
            ->cannotBeEmpty()
            ->requiresAtLeastOneElement()
            ->validate()
                ->ifTrue(fn ($data) => count($data) <= 0)
                ->thenUnset()
            ->end()
            ->arrayPrototype()
                ->children()
                    ->scalarNode('condition')
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
                        ->end()
                    ->end()
                    ->scalarNode('query')
                        ->isRequired()
                        ->validate()
                            ->ifTrue(fn ($data) => is_string($data) && $data !== '' && (!str_starts_with($data, 'SELECT') && !str_starts_with($data, 'select')))
                            ->thenInvalid('Your query should be start with "SELECT".')
                        ->end()
                    ->end()
                    ->arrayNode('params')
                        ->variablePrototype()
                            ->validate()
                                ->ifArray()
                                ->thenInvalid('A parameter cann\'t be an array.')
                            ->end()
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
                    ->append((new FastMap\Configuration('merge'))->getConfigTreeBuilder()->getRootNode())
                ->end()
            ->end()
        ->end();

        return $builder;
    }
}
