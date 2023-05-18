<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Factory;

use Kiboko\Component\Packaging\Asset\InMemory;
use Kiboko\Component\Packaging\File;
use Kiboko\Contract\Configurator\FactoryInterface;
use Kiboko\Contract\Configurator\InvalidConfigurationException;
use Kiboko\Plugin\SQL;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final readonly class Connection implements FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct(
        private ExpressionLanguage $interpreter,
        private string $generatedNamespace = 'GyroscopsGenerated',
    ) {
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
        if (\array_key_exists('shared', $config) && true === $config['shared']) {
            $connection = new SQL\Builder\SharedConnection(
                compileValueWhenExpression($this->interpreter, $config['dsn']),
                generatedNamespace: $this->generatedNamespace,
            );
        } else {
            $connection = new SQL\Builder\Connection(
                compileValueWhenExpression($this->interpreter, $config['dsn']),
                generatedNamespace: $this->generatedNamespace,
            );
        }

        if (\array_key_exists('username', $config)) {
            $connection->withUsername(compileValueWhenExpression($this->interpreter, $config['username']));
        }

        if (\array_key_exists('password', $config)) {
            $connection->withPassword(compileValueWhenExpression($this->interpreter, $config['password']));
        }

        if (\array_key_exists('options', $config)) {
            if (\array_key_exists('persistent', $config['options'])) {
                $connection->withPersistentConnection($config['options']['persistent']);
            }
        }

        $repository = new SQL\Factory\Repository\Connection($connection);

        $repository->addFiles(new File('PDOPool.php', new InMemory(<<<PHP
            <?php

            namespace {$this->generatedNamespace};
            final class PDOPool {
                private static array \$connections = [];
                public static function unique(string \$dsn, ?string \$username = null, ?string \$password = null, \$options = []): \\PDO {
                    return new \\PDO(\$dsn, \$username, \$password, \$options);
                }
                public static function shared(string \$dsn, ?string \$username = null, ?string \$password = null, \$options = []): \\PDO {
                    if (isset(self::\$connections[\$dsn])) {
                        return self::\$connections[\$dsn];
                    }
                    
                    return self::\$connections[\$dsn] = self::unique(\$dsn, \$username, \$password, \$options);
                }
            }
            PHP
        )));

        return $repository;
    }
}
