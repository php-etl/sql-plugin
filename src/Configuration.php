<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL;

use Kiboko\Component\Satellite\NamedConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface, NamedConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

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

    public function getName(): string
    {
        return 'sql';
    }
}
