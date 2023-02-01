<?php

declare(strict_types=1);

namespace functional\Kiboko\Plugin\SQL\utils;

use PHPUnit\Framework\Constraint\Constraint;

/** @template Type */
final class SQLiteTableExists extends Constraint
{
    public function __construct(
        private readonly \PDO $connection,
        private readonly string $table,
    ) {
    }

    public function matches($other): bool
    {
        $stmt = $this->connection->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:name");
        $stmt->bindValue(':name', $this->table);

        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_NAMED);

        if ($result !== $other) {
            return true;
        }

        return false;
    }

    protected function failureDescription($other): string
    {
        return sprintf(
            'table %s exists',
            $this->exporter()->export($this->table),
        );
    }

    public function toString(): string
    {
        return 'table exists';
    }
}
