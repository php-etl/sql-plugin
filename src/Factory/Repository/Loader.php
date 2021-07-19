<?php

namespace Kiboko\Plugin\SQL\Factory\Repository;

use Kiboko\Plugin\SQL;
use Kiboko\Contract\Configurator\StepRepositoryInterface;
use Kiboko\Contract\Configurator;

class Loader implements StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private SQL\Builder\Loader $builder)
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

    public function withBeforeQuery(?InitializerQuery $query): self
    {
        if ($query === null) {
            return $this;
        }

        $this->merge($query);
        $this->builder->withBeforeQuery($query->getBuilder());

        return $this;
    }

    public function withBeforeQueries(InitializerQuery ...$queries): self
    {
        foreach ($queries as $query) {
            $this->withBeforeQuery($query);
        }

        return $this;
    }

    public function withAfterQuery(?InitializerQuery $query): self
    {
        if ($query === null) {
            return $this;
        }

        $this->merge($query);
        $this->builder->withAfterQuery($query->getBuilder());

        return $this;
    }

    public function withAfterQueries(InitializerQuery ...$queries): self
    {
        foreach ($queries as $query) {
            $this->withAfterQuery($query);
        }

        return $this;
    }

    public function getBuilder(): SQL\Builder\Loader
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
