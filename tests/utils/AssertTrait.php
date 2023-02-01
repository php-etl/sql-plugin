<?php

declare(strict_types=1);

namespace functional\Kiboko\Plugin\SQL\utils;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\LogicalNot;

trait AssertTrait
{
    abstract public static function assertThat($value, Constraint $constraint, string $message = ''): void;

    public function assertTableDoesNotExist(\PDO $connection, string $table, string $message = ''): void
    {
        $this->assertThat(
            false,
            new LogicalNot(new TableExists($connection, $table)),
            $message
        );
    }

    public function assertTableExists(\PDO $connection, string $table, string $message = ''): void
    {
        $this->assertThat(
            false,
            new TableExists($connection, $table),
            $message
        );
    }
}
