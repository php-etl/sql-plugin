<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Plugin\SQL;

final class InitializerQuery implements Configurator\RepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private readonly SQL\Builder\InitializerQueries $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): SQL\Builder\InitializerQueries
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
