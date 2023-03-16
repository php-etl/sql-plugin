<?php

declare(strict_types=1);

namespace functional\Kiboko\Plugin\SQL\utils;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\LogicalNot;

trait AssertTrait
{
    abstract public static function assertThat($value, Constraint $constraint, string $message = ''): void;

    public function assertSQLiteTableDoesNotExist(\PDO $connection, string $table, string $message = ''): void
    {
        static::assertThat(false, new LogicalNot(new SQLiteTableExists($connection, $table)), $message);
    }

    public function assertSQLiteTableExists(\PDO $connection, string $table, string $message = ''): void
    {
        static::assertThat(false, new SQLiteTableExists($connection, $table), $message);
    }
}
