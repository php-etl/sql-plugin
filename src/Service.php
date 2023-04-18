<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL;

use Kiboko\Contract\Configurator;
use Kiboko\Plugin\SQL\Factory\Connection;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[Configurator\Pipeline(
    name: 'sql',
    dependencies: [
        'ext-pdo',
        'php-etl/sql-flow:*',
    ],
    steps: [
        new Configurator\Pipeline\StepExtractor(),
        new Configurator\Pipeline\StepTransformer('lookup'),
        new Configurator\Pipeline\StepLoader(),
    ],
)]
final readonly class Service implements Configurator\PipelinePluginInterface
{
    private Processor $processor;
    private Configurator\PluginConfigurationInterface $configuration;

    public function __construct(
        private ExpressionLanguage $interpreter = new ExpressionLanguage(),
        private string $generatedNamespace = 'GyroscopsGenerated',
    ) {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function interpreter(): ExpressionLanguage
    {
        return $this->interpreter;
    }

    public function configuration(): Configurator\PluginConfigurationInterface
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
        $interpreter = clone $this->interpreter;

        if (\array_key_exists('expression_language', $config)
            && \is_array($config['expression_language'])
            && \count($config['expression_language'])
        ) {
            foreach ($config['expression_language'] as $provider) {
                $interpreter->registerProvider(new $provider());
            }
        }

        $connection = (new Connection($interpreter, $this->generatedNamespace))->compile($config['connection']);

        if (\array_key_exists('extractor', $config)) {
            $extractorFactory = new Factory\Extractor($interpreter);

            return $extractorFactory
                ->compile($config['extractor'])
                ->withConnection($connection)
                ->withBeforeQueries(...($config['before']['queries'] ?? []))
                ->withAfterQueries(...($config['after']['queries'] ?? []))
            ;
        }
        if (\array_key_exists('lookup', $config)) {
            $lookupFactory = new Factory\Lookup($interpreter);

            return $lookupFactory
                ->compile($config['lookup'])
                ->withConnection($connection)
                ->withBeforeQueries(...($config['before']['queries'] ?? []))
                ->withAfterQueries(...($config['after']['queries'] ?? []))
            ;
        }
        if (\array_key_exists('loader', $config)) {
            $loaderFactory = new Factory\Loader($interpreter);

            return $loaderFactory
                ->compile($config['loader'])
                ->withConnection($connection)
                ->withBeforeQueries(...($config['before']['queries'] ?? []))
                ->withAfterQueries(...($config['after']['queries'] ?? []))
            ;
        }
        throw new Configurator\InvalidConfigurationException('Could not determine if the factory should build an extractor, a lookup or a loader.');
    }
}
