<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Factory;

use Kiboko\Plugin\FastMap;
use Kiboko\Component\FastMapConfig;
use Kiboko\Contract\Configurator\InvalidConfigurationException;
use Kiboko\Plugin\SQL;
use Kiboko\Contract\Configurator\FactoryInterface;
use Kiboko\Contract\Configurator\RepositoryInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Config\Definition\Exception as Symfony;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final class Lookup implements FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct(private ExpressionLanguage $interpreter)
    {
        $this->processor = new Processor();
        $this->configuration = new SQL\Configuration\Extractor();
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

    private function merge(SQL\Builder\AlternativeLookup $alternativeBuilder, array $config)
    {
        if (array_key_exists('merge', $config)) {
            if (array_key_exists('map', $config['merge'])) {
                $mapper = new FastMapConfig\ArrayAppendBuilder(
                    interpreter: $this->interpreter,
                );

                $fastMap = new SQL\Builder\Inline($mapper);

                (new FastMap\Configuration\ConfigurationApplier(['lookup' => []]))($mapper->children(), $config['merge']['map']);

                $alternativeBuilder->withMerge($fastMap);
            }
        }
    }

    public function compile(array $config): RepositoryInterface
    {
        if (!array_key_exists('conditional', $config)) {
            $alternativeBuilder = new SQL\Builder\AlternativeLookup(
                compileValueWhenExpression($this->interpreter, $config["query"]),
                compileValueWhenExpression($this->interpreter, $config["connection"]["dsn"])
            );

            $lookup = new SQL\Builder\Lookup($alternativeBuilder);

            if (array_key_exists('username', $config["connection"])) {
                $alternativeBuilder->withUsername(compileValueWhenExpression($this->interpreter, $config["connection"]["username"]));
            }

            if (array_key_exists('password', $config["connection"])) {
                $alternativeBuilder->withPassword(compileValueWhenExpression($this->interpreter, $config["connection"]["password"]));
            }

            if (array_key_exists('params', $config)) {
                foreach ($config["params"] as $key => $param) {
                    $alternativeBuilder->addParam($key, compileValueWhenExpression($this->interpreter, $param));
                }
            }

            $this->merge($alternativeBuilder, $config);
        } else {
            $lookup = new SQL\Builder\ConditionalLookup();

            foreach ($config['conditional'] as $alternative) {
                $alternativeBuilder = new SQL\Builder\AlternativeLookup(
                    compileValueWhenExpression($this->interpreter, $alternative["query"]),
                    compileValueWhenExpression($this->interpreter, $alternative["connection"]["dsn"])
                );

                if (array_key_exists('username', $alternative["connection"])) {
                    $alternativeBuilder->withUsername(compileValueWhenExpression($this->interpreter, $alternative["connection"]["username"]));
                }

                if (array_key_exists('password', $alternative["connection"])) {
                    $alternativeBuilder->withPassword(compileValueWhenExpression($this->interpreter, $alternative["connection"]["password"]));
                }

                if (array_key_exists('params', $alternative)) {
                    foreach ($alternative["params"] as $key => $param) {
                        $alternativeBuilder->addParam($key, compileValueWhenExpression($this->interpreter, $param));
                    }
                }

                $lookup->addAlternative(
                    compileValueWhenExpression($this->interpreter, $alternative['condition']),
                    $alternativeBuilder
                );

                $this->merge($alternativeBuilder, $alternative);
            }
        }

        return new Repository\Lookup($lookup);
    }
}
