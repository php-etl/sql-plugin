<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final readonly class InitializerQueries implements Builder
{
    public function __construct(
        private Node\Expr $query,
    ) {
    }

    public function getNode(): Node\Expr
    {
        return $this->query;
    }
}
