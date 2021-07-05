<?php

namespace Kiboko\Plugin\SQL\Factory\Repository;

use Kiboko\Plugin\SQL;
use Kiboko\Contract\Configurator\StepRepositoryInterface;

class Extractor implements StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private SQL\Builder\Extractor $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): SQL\Builder\Extractor
    {
        return $this->builder;
    }
}
