<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL;

use Kiboko\Contract\Configurator\ConfiguratorExtractorInterface;
use Kiboko\Contract\Configurator\ConfiguratorLoaderInterface;
use Kiboko\Contract\Configurator\ConfiguratorTransformerInterface;
use Kiboko\Contract\Configurator\FactoryInterface;
use Kiboko\Contract\Configurator\InvalidConfigurationException;
use Kiboko\Contract\Configurator\RepositoryInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Config\Definition\Exception as Symfony;

final class Service implements FactoryInterface, ConfiguratorExtractorInterface, ConfiguratorTransformerInterface, ConfiguratorLoaderInterface
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
            throw new InvalidConfigurationException($exception->getMessage(), 0, $exception);
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

    public function compile(array $config): RepositoryInterface
    {
        if (array_key_exists('expression_language', $config)
            && is_array($config['expression_language'])
            && count($config['expression_language'])
        ) {
            foreach ($config['expression_language'] as $provider) {
                $this->interpreter->registerProvider(new $provider);
            }
        }

        try {
            if (array_key_exists('extractor', $config)) {
                $extractorFactory = new Factory\Extractor($this->interpreter);

                return $extractorFactory->compile($config['extractor']);
            } elseif (array_key_exists('lookup', $config)) {
                $lookupFactory = new Factory\Lookup($this->interpreter);

                return $lookupFactory->compile($config['lookup']);
            } elseif (array_key_exists('loader', $config)) {
                $loaderFactory = new Factory\Loader($this->interpreter);

                return $loaderFactory->compile($config['loader']);
            } else {
                throw new InvalidConfigurationException(
                    'Could not determine if the factory shoul d build an extractor or a loader.'
                );
            }
        } catch (InvalidConfigurationException $exception) {
            throw new InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function getExtractorKey(): string
    {
        return 'extractor';
    }

    public function getLoaderKeys(): array
    {
        return ['loader'];
    }

    public function getTransformerKeys(): ?array
    {
        return ['lookup'];
    }
}
