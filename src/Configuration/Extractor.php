<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Extractor implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('extractor');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->append((new Query())->getConfigTreeBuilder()->getRootNode()
                ->validate()
                    ->ifTrue(isExpression())
                    ->then(asExpression())
                ->end()
            )
            ->append((new Parameters())->getConfigTreeBuilder()->getRootNode())
        ;

        return $builder;
    }
}
