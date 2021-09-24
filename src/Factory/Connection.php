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

final class Connection implements FactoryInterface
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

    public function compile(array $config): SQL\Factory\Repository\Connection
    {
        $extractor = new SQL\Builder\Connection(
            compileValueWhenExpression($this->interpreter, $config['dsn'])
        );

        if (array_key_exists('username', $config)) {
            $extractor->withUsername(compileValueWhenExpression($this->interpreter, $config['username']));
        }

        if (array_key_exists('password', $config)) {
            $extractor->withPassword(compileValueWhenExpression($this->interpreter, $config['password']));
        }

        if (array_key_exists('options', $config)) {
            if (array_key_exists('persistent', $config["options"])) {
                $extractor->withPersistentConnection($config['options']['persistent']);
            }
        }

        return new SQL\Factory\Repository\Connection($extractor);
    }
}
