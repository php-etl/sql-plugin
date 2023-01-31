<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration\Connection;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Options implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('options');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->ifTrue(fn (array $data) => \count($data) <= 0)
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
}
