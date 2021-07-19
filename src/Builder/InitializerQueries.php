<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class InitializerQueries implements Builder
{
    public function __construct(
        private Node\Expr $connection,
        private string $query,
    ) {}

    public function getNode(): Node
    {
        return new Node\Expr\MethodCall(
            var: $this->connection,
            name: 'exec',
            args: [
                new Node\Arg(new Node\Scalar\String_($this->query))
            ]
        );
    }
}
