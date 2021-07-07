<?php

namespace Kiboko\Plugin\SQL\Factory\Repository;

use Kiboko\Plugin\SQL;
use Kiboko\Contract\Configurator\StepRepositoryInterface;
use Kiboko\Contract\Configurator;

class Lookup implements StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private SQL\Builder\Lookup|SQL\Builder\ConditionalLookup $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): SQL\Builder\Lookup|SQL\Builder\ConditionalLookup
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
