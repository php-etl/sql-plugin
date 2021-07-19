<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Factory\Repository;

use Kiboko\Plugin\SQL;
use Kiboko\Contract\Configurator;

final class Connection implements Configurator\RepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private SQL\Builder\Connection $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): SQL\Builder\Connection
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
