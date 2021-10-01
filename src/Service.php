<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL;

use Kiboko\Contract\Configurator;
use Kiboko\Plugin\SQL\Factory\Connection;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Config\Definition\Exception as Symfony;

#[Configurator\Pipeline(
    name: "sql",
    dependencies: [
        'ext-pdo',
    ],
    steps: [
        "extractor" => "extractor",
        "lookup" => "transformer",
        "loader" => "loader",
    ],
)]
final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(?ExpressionLanguage $interpreter = null)
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
        $this->interpreter = $interpreter ?? new ExpressionLanguage();
    }

    public function configuration(): ConfigurationInterface
    {
        return $this->configuration;
    }

    public function normalize(array $config): array
    {
        try {
            return $this->processor->processConfiguration($this->configuration, $config);
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

            return true;
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException) {
            return false;
        }
    }

    public function compile(array $config): Factory\Repository\Extractor|Factory\Repository\Lookup|Factory\Repository\Loader
    {
        if (array_key_exists('expression_language', $config)
            && is_array($config['expression_language'])
            && count($config['expression_language'])
        ) {
            foreach ($config['expression_language'] as $provider) {
                $this->interpreter->registerProvider(new $provider);
            }
        }

        $connection = (new Connection($this->interpreter))->compile($config['connection']);

        if (array_key_exists('extractor', $config)) {
            $extractorFactory = new Factory\Extractor($this->interpreter);

            return $extractorFactory
                ->compile($config['extractor'])
                ->withConnection($connection)
                ->withBeforeQueries(...($config['before']['queries'] ?? []))
                ->withAfterQueries(...($config['after']['queries'] ?? []));
        } elseif (array_key_exists('lookup', $config)) {
            $lookupFactory = new Factory\Lookup($this->interpreter);

            return $lookupFactory
                ->compile($config['lookup'])
                ->withConnection($connection)
                ->withBeforeQueries(...($config['before']['queries'] ?? []))
                ->withAfterQueries(...($config['after']['queries'] ?? []));
        } elseif (array_key_exists('loader', $config)) {
            $loaderFactory = new Factory\Loader($this->interpreter);

            return $loaderFactory
                ->compile($config['loader'])
                ->withConnection($connection)
                ->withBeforeQueries(...($config['before']['queries'] ?? []))
                ->withAfterQueries(...($config['after']['queries'] ?? []));
        } else {
            throw new Configurator\InvalidConfigurationException(
                'Could not determine if the factory should build an extractor, a lookup or a loader.'
            );
        }
    }
}
