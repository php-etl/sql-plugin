<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements \Symfony\Component\Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('sql');

        $builder->getRootNode()
            ->validate()
                ->ifTrue(function (array $value) {
                    return array_key_exists('extractor', $value) && array_key_exists('loader', $value);
                })
                ->thenInvalid('Your configuration should either contain the "extractor" or the "loader" key, not both.')
            ->end()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->append(node: (new Configuration\Extractor())->getConfigTreeBuilder()->getRootNode())
                ->append(node: (new Configuration\Lookup())->getConfigTreeBuilder()->getRootNode())
                ->append(node: (new Configuration\Loader())->getConfigTreeBuilder()->getRootNode())
            ->end()
        ;

        return $builder;
    }
}
