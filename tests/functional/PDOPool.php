<?php

declare(strict_types=1);

namespace functional\Kiboko\Plugin\SQL;

final class PDOPool
{
    private static array $connections = [];

    public static function unique(string $dsn, ?string $username = null, ?string $password = null, $options = []): \PDO
    {
        return new \PDO($dsn, $username, $password, $options);
    }

    public static function shared(string $dsn, ?string $username = null, ?string $password = null, $options = []): \PDO
    {
        if (isset(self::$connections[$dsn])) {
            return self::$connections[$dsn];
        }

        return self::$connections[$dsn] = self::unique($dsn, $username, $password, $options);
    }
}
