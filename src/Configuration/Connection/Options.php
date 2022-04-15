<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration\Connection;

use Kiboko\Plugin\FastMap;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Options implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('options');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->ifTrue(function (array $data) {
                    return count($data) <= 0;
                })
                ->thenUnset()
            ->end()
            ->children()
                ->booleanNode('persistent')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }

    private function getConditionalTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('conditional');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->cannotBeEmpty()
            ->requiresAtLeastOneElement()
            ->validate()
                ->ifTrue(fn ($data) => count($data) <= 0)
                ->thenUnset()
            ->end()
            ->arrayPrototype()
                ->children()
                    ->variableNode('condition')
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
                        ->end()
                    ->end()
                    ->append((new Query())->getConfigTreeBuilder()->getRootNode())
                    ->append((new Parameters())->getConfigTreeBuilder()->getRootNode())
                    ->append((new FastMap\Configuration('merge'))->getConfigTreeBuilder()->getRootNode())
                ->end()
            ->end()
        ->end();

        return $builder;
    }
}
