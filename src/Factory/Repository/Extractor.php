<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Contract\Configurator\StepRepositoryInterface;
use Kiboko\Plugin\SQL;
use PhpParser\Node;

class Extractor implements StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private readonly SQL\Builder\Extractor $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function withConnection(Connection $connection): self
    {
        $this->merge($connection);
        $this->builder->withConnection($connection->getBuilder());

        return $this;
    }

    public function withBeforeQuery(string $query): self
    {
        $this->builder->withBeforeQuery(
            new SQL\Builder\InitializerQueries(new Node\Scalar\String_($query))
        );

        return $this;
    }

    public function withBeforeQueries(string ...$queries): self
    {
        foreach ($queries as $query) {
            $this->builder->withBeforeQuery(
                new SQL\Builder\InitializerQueries(new Node\Scalar\String_($query))
            );
        }

        return $this;
    }

    public function withAfterQuery(string $query): self
    {
        $this->builder->withAfterQuery(
            new SQL\Builder\InitializerQueries(new Node\Scalar\String_($query))
        );

        return $this;
    }

    public function withAfterQueries(string ...$queries): self
    {
        foreach ($queries as $query) {
            $this->builder->withAfterQuery(
                new SQL\Builder\InitializerQueries(new Node\Scalar\String_($query))
            );
        }

        return $this;
    }

    public function getBuilder(): SQL\Builder\Extractor
    {
        return $this->builder;
    }

    public function merge(Configurator\RepositoryInterface $friend): self
    {
        array_push($this->files, ...$friend->getFiles());
        array_push($this->packages, ...$friend->getPackages());

        return $this;
    }
}
