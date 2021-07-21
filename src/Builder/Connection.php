<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Connection implements Builder
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function __construct(
        private Node\Expr $dsn,
        private ?Node\Expr $username = null,
        private ?Node\Expr $password = null,
    ) {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
    }

    public function withUsername(Node\Expr $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function withPassword(Node\Expr $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('PDO'),
            args: [
                new Node\Arg($this->dsn),
                $this->username ? new Node\Arg($this->username) : new Node\Expr\ConstFetch(new Node\Name('null')),
                $this->password ? new Node\Arg($this->password) : new Node\Expr\ConstFetch(new Node\Name('null')),
            ],
        );
    }
}
