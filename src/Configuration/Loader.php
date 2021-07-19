<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Configuration;

use Kiboko\Plugin\FastMap;
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
             ->validate()
                ->ifTrue(fn ($data) => array_key_exists('conditional', $data) && is_array($data['conditional']) && count($data['conditional']) <= 0)
                ->then(function ($data) {
                    unset($data['conditional']);
                    return $data;
                })
            ->end()
             ->validate()
                ->ifTrue(fn ($data) => array_key_exists('parameters', $data) && is_array($data['parameters']) && count($data['parameters']) <= 0)
                ->then(function ($data) {
                    unset($data['parameters']);
                    return $data;
                })
            ->end()
            ->append((new Query())->getConfigTreeBuilder()->getRootNode())
            ->append((new Parameters())->getConfigTreeBuilder()->getRootNode())
            ->children()
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
