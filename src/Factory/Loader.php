<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Factory;

use Kiboko\Contract\Configurator\FactoryInterface;
use Kiboko\Contract\Configurator\InvalidConfigurationException;
use Kiboko\Plugin\SQL;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final readonly class Loader implements FactoryInterface
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
        if (!\array_key_exists('conditional', $config)) {
            $loader = new SQL\Builder\Loader(
                compileValueWhenExpression($this->interpreter, $config['query']),
            );

            if (\array_key_exists('parameters', $config)) {
                foreach ($config['parameters'] as $key => $parameter) {
                    if (\array_key_exists('from', $parameter) && isset($parameter['from'])) {
                        match (\array_key_exists('type', $parameter) ? $parameter['type'] : null) {
                            'integer' => $loader->addIntegerParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'boolean' => $loader->addBooleanParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'date' => $loader->addDateParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'datetime' => $loader->addDateTimeParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'json' => $loader->addJSONParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'binary' => $loader->addBinaryParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            default => $loader->addStringParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                        };
                    } else {
                        match (\array_key_exists('type', $parameter) ? $parameter['type'] : null) {
                            'integer' => $loader->addIntegerParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'boolean' => $loader->addBooleanParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'date' => $loader->addDateParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'datetime' => $loader->addDateTimeParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'json' => $loader->addJSONParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'binary' => $loader->addBinaryParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            default => $loader->addStringParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                        };
                    }
                }
            }
        } else {
            $loader = new SQL\Builder\ConditionalLoader();

            foreach ($config['conditional'] as $alternative) {
                $alternativeLoaderBuilder = new SQL\Builder\AlternativeLoader(
                    compileValueWhenExpression($this->interpreter, $alternative['query'])
                );

                if (\array_key_exists('parameters', $alternative)) {
                    foreach ($alternative['parameters'] as $key => $parameter) {
                        if (\array_key_exists('from', $parameter) && isset($parameter['from'])) {
                            match (\array_key_exists('type', $parameter) ? $parameter['type'] : null) {
                                'integer' => $alternativeLoaderBuilder->addIntegerParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from'])
                                ),
                                'boolean' => $alternativeLoaderBuilder->addBooleanParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from'])
                                ),
                                'date' => $alternativeLoaderBuilder->addDateParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from'])
                                ),
                                'datetime' => $alternativeLoaderBuilder->addDateTimeParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from'])
                                ),
                                'json' => $alternativeLoaderBuilder->addJSONParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from'])
                                ),
                                'binary' => $alternativeLoaderBuilder->addBinaryParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from'])
                                ),
                                default => $alternativeLoaderBuilder->addStringParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from'])
                                ),
                            };
                        } else {
                            match (\array_key_exists('type', $parameter) ? $parameter['type'] : null) {
                                'integer' => $alternativeLoaderBuilder->addIntegerParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value'])
                                ),
                                'boolean' => $alternativeLoaderBuilder->addBooleanParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value'])
                                ),
                                'date' => $alternativeLoaderBuilder->addDateParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value'])
                                ),
                                'datetime' => $alternativeLoaderBuilder->addDateTimeParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value'])
                                ),
                                'json' => $alternativeLoaderBuilder->addJSONParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value'])
                                ),
                                'binary' => $alternativeLoaderBuilder->addBinaryParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value'])
                                ),
                                default => $alternativeLoaderBuilder->addStringParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value'])
                                ),
                            };
                        }
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
