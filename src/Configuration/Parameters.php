<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Parameters implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('parameters');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->ifTrue(fn ($data) => \count($data) <= 0)
                ->thenUnset()
            ->end()
            ->useAttributeAsKey('key', false)
            ->arrayPrototype()
                ->children()
                    ->scalarNode('value')
                        ->isRequired()
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
                        ->end()
                    ->end()
                    ->enumNode('type')
                        ->values(['boolean', 'integer', 'string', 'date', 'datetime', 'json', 'binary'])
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
