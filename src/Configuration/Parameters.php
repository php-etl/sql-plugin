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
            ->useAttributeAsKey('key')
            ->variablePrototype()
                ->isRequired()
                ->cannotBeEmpty()
                ->validate()
                    ->ifTrue(isExpression())
                    ->then(asExpression())
                ->end()
            ->end();

        return $builder;
    }
}
