<?php

namespace Kiboko\Plugin\SQL\Factory\Repository;

use Kiboko\Plugin\SQL;
use Kiboko\Contract\Configurator\StepRepositoryInterface;

class Loader implements StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private SQL\Builder\Loader $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): SQL\Builder\Loader
    {
        return $this->builder;
    }
}
