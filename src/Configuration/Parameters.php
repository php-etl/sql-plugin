<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

class Parameters implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('parameters');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->cannotBeEmpty()
            ->requiresAtLeastOneElement()
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
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifTrue(fn ($data) => !is_string($data) && !is_int($data))
                            ->thenInvalid('The key of your parameter must be a string or an integer.')
                        ->end()
                         ->validate()
                            ->ifTrue(fn ($data) => is_int($data) && $data < 1)
                            ->thenInvalid('The key of your parameter must be greater or equal to 1.')
                        ->end()
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
                        ->end()
                    ->end()
                    ->variableNode('value')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
