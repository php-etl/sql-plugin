<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\mutuallyExclusiveFields;

class Parameters implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('parameters');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->ifTrue(fn ($data) => (is_countable($data) ? \count($data) : 0) <= 0)
                ->thenUnset()
            ->end()
            ->useAttributeAsKey('key', false)
            ->arrayPrototype()
                ->validate()
                    ->always(mutuallyExclusiveFields('value', 'from'))
                ->end()
                ->validate()
                    ->ifTrue(fn (array $data) => !\array_key_exists('value', $data) && !\array_key_exists('from', $data))
                    ->thenInvalid('Your configuration should either contain the "value" or the "from" key.')
                ->end()
                ->children()
                    ->scalarNode('value')
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
                        ->end()
                    ->end()
                    ->scalarNode('from')
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
