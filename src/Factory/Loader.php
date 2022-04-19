<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Factory;

use Kiboko\Contract\Configurator\InvalidConfigurationException;
use Kiboko\Plugin\SQL;
use Kiboko\Contract\Configurator\FactoryInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Config\Definition\Exception as Symfony;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValue;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final class Loader implements FactoryInterface
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

    public function compile(array $config): SQL\Factory\Repository\Loader
    {
        if (!array_key_exists('conditional', $config)) {
            $loader = new SQL\Builder\Loader(
                compileValueWhenExpression($this->interpreter, $config["query"]),
            );

            if (array_key_exists('parameters', $config)) {
                foreach ($config["parameters"] as $parameter) {
                    $loader->addParam(
                        new SQL\Builder\DTO\Parameter(
                            $parameter["key"],
                            compileValueWhenExpression($this->interpreter, $parameter["value"]),
                            array_key_exists('type', $parameter) ? $parameter["type"] : null,
                        )
                    );
                }
            }
        } else {
            $loader = new SQL\Builder\ConditionalLoader();

            foreach ($config['conditional'] as $alternative) {
                $alternativeLoaderBuilder = new SQL\Builder\AlternativeLoader(
                    compileValueWhenExpression($this->interpreter, $alternative["query"])
                );

                if (array_key_exists('parameters', $alternative)) {
                    foreach ($alternative["parameters"] as $parameter) {
                        $alternativeLoaderBuilder->addParam(
                            new SQL\Builder\DTO\Parameter(
                                $parameter["key"],
                                compileValueWhenExpression($this->interpreter, $parameter["value"]),
                                array_key_exists('type', $parameter) ? $parameter["type"] : null,
                            )
                        );
                    }
                }

                $loader->addAlternative(
                    compileValueWhenExpression($this->interpreter, $alternative['condition']),
                    $alternativeLoaderBuilder
                );
            }
        }

        return new Repository\Loader($loader);
    }
}
