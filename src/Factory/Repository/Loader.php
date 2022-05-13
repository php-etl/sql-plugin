<?php

namespace Kiboko\Plugin\SQL\Factory\Repository;

use Kiboko\Plugin\SQL;
use Kiboko\Contract\Configurator\StepRepositoryInterface;
use Kiboko\Contract\Configurator;
use PhpParser\Node;
use function Kiboko\Component\SatelliteToolbox\AST\variable;

class Loader implements StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private SQL\Builder\Loader|SQL\Builder\ConditionalLoader $builder)
    {
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

    public function getBuilder(): SQL\Builder\Loader|SQL\Builder\ConditionalLoader
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
