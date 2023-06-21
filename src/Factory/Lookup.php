<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Factory;

use Kiboko\Component\FastMapConfig;
use Kiboko\Contract\Configurator\FactoryInterface;
use Kiboko\Contract\Configurator\InvalidConfigurationException;
use Kiboko\Plugin\FastMap;
use Kiboko\Plugin\SQL;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final readonly class Lookup implements FactoryInterface
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

    /**
     * @param array<array> $config
     */
    private function merge(SQL\Builder\AlternativeLookup $alternativeBuilder, array $config): void
    {
        if (\array_key_exists('merge', $config)) {
            if (\array_key_exists('map', $config['merge'])) {
                $mapper = new FastMapConfig\ArrayAppendBuilder(
                    interpreter: $this->interpreter,
                );

                $fastMap = new SQL\Builder\Inline($mapper);

                (new FastMap\Configuration\ConfigurationApplier(['lookup' => []]))($mapper->children(), $config['merge']['map']);

                $alternativeBuilder->withMerge($fastMap);
            }
        }
    }

    public function compile(array $config): SQL\Factory\Repository\Lookup
    {
        if (!\array_key_exists('conditional', $config)) {
            $alternativeBuilder = new SQL\Builder\AlternativeLookup(
                compileValueWhenExpression($this->interpreter, $config['query'])
            );

            $lookup = new SQL\Builder\Lookup($alternativeBuilder);

            if (\array_key_exists('parameters', $config)) {
                foreach ($config['parameters'] as $key => $parameter) {
                    if (\array_key_exists('from', $parameter) && isset($parameter['from'])) {
                        match (\array_key_exists('type', $parameter) ? $parameter['type'] : null) {
                            'integer' => $alternativeBuilder->addIntegerParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'boolean' => $alternativeBuilder->addBooleanParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'date' => $alternativeBuilder->addDateParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'datetime' => $alternativeBuilder->addDateTimeParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'json' => $alternativeBuilder->addJSONParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            'binary' => $alternativeBuilder->addBinaryParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                            default => $alternativeBuilder->addStringParamList(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['from']),
                            ),
                        };
                    } else {
                        match (\array_key_exists('type', $parameter) ? $parameter['type'] : null) {
                            'integer' => $alternativeBuilder->addIntegerParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'boolean' => $alternativeBuilder->addBooleanParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'date' => $alternativeBuilder->addDateParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'datetime' => $alternativeBuilder->addDateTimeParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'json' => $alternativeBuilder->addJSONParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            'binary' => $alternativeBuilder->addBinaryParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                            default => $alternativeBuilder->addStringParam(
                                $key,
                                compileValueWhenExpression($this->interpreter, $parameter['value']),
                            ),
                        };
                    }
                }
            }

            $this->merge($alternativeBuilder, $config);
        } else {
            $lookup = new SQL\Builder\ConditionalLookup();

            foreach ($config['conditional'] as $alternative) {
                $alternativeBuilder = new SQL\Builder\AlternativeLookup(
                    compileValueWhenExpression($this->interpreter, $alternative['query'])
                );

                if (\array_key_exists('parameters', $alternative)) {
                    foreach ($alternative['parameters'] as $key => $parameter) {
                        if (\array_key_exists('from', $parameter) && isset($parameter['from'])) {
                            match (\array_key_exists('type', $parameter) ? $parameter['type'] : null) {
                                'integer' => $alternativeBuilder->addIntegerParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from']),
                                ),
                                'boolean' => $alternativeBuilder->addBooleanParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from']),
                                ),
                                'date' => $alternativeBuilder->addDateParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from']),
                                ),
                                'datetime' => $alternativeBuilder->addDateTimeParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from']),
                                ),
                                'json' => $alternativeBuilder->addJSONParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from']),
                                ),
                                'binary' => $alternativeBuilder->addBinaryParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from']),
                                ),
                                default => $alternativeBuilder->addStringParamList(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['from']),
                                ),
                            };
                        } else {
                            match (\array_key_exists('type', $parameter) ? $parameter['type'] : null) {
                                'integer' => $alternativeBuilder->addIntegerParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value']),
                                ),
                                'boolean' => $alternativeBuilder->addBooleanParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value']),
                                ),
                                'date' => $alternativeBuilder->addDateParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value']),
                                ),
                                'datetime' => $alternativeBuilder->addDateTimeParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value']),
                                ),
                                'json' => $alternativeBuilder->addJSONParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value']),
                                ),
                                'binary' => $alternativeBuilder->addBinaryParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value']),
                                ),
                                default => $alternativeBuilder->addStringParam(
                                    $key,
                                    compileValueWhenExpression($this->interpreter, $parameter['value']),
                                ),
                            };
                        }
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
