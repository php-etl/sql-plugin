<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

class Parameters implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('parameters');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->ifTrue(function ($data) {
                    return count($data) <= 0;
                })
                ->thenUnset()
            ->end()
            ->arrayPrototype()
                ->children()
                    ->variableNode('key')
                        ->isRequired()
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
                        ->end()
                        ->validate()
                            ->ifTrue(function ($value) {
                                return !is_string($value) && !is_int($value);
                            })
                            ->thenInvalid('The parameter\'s key must be of a string or an integer.')
                        ->end()
                    ->end()
                    ->scalarNode('value')
                        ->isRequired()
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
                        ->end()
                    ->end()
                    ->enumNode('type')
                        ->values(['boolean', 'integer'])
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
