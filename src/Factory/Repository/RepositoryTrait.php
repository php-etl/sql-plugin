<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging\DirectoryInterface;
use Kiboko\Contract\Packaging\FileInterface;

trait RepositoryTrait
{
    /** @var array<FileInterface|DirectoryInterface> */
    private array $files;
    /** @var string[] */
    private array $packages;

    public function addFiles(FileInterface|DirectoryInterface ...$files): Configurator\RepositoryInterface
    {
        array_push($this->files, ...$files);

        return $this;
    }

    /** @return iterable<FileInterface|DirectoryInterface> */
    public function getFiles(): iterable
    {
        return $this->files;
    }

    public function addPackages(string ...$packages): Configurator\RepositoryInterface
    {
        array_push($this->packages, ...$packages);

        return $this;
    }

    /** @return iterable<string> */
    public function getPackages(): iterable
    {
        return $this->packages;
    }

    public function merge(Configurator\RepositoryInterface $friend): Configurator\RepositoryInterface
    {
        array_push($this->files, ...$friend->getFiles());
        array_push($this->packages, ...$friend->getPackages());

        return $this;
    }
}
