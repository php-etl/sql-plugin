<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use PhpParser\Node;

final class Connection implements ConnectionBuilderInterface
{
    private ?bool $persistentConnection = null;

    public function __construct(
        private readonly Node\Expr $dsn,
        private ?Node\Expr $username = null,
        private ?Node\Expr $password = null,
        private readonly string $generatedNamespace = 'GyroscopsGenerated'
    ) {
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

    public function withPersistentConnection(bool $option): self
    {
        $this->persistentConnection = $option;

        return $this;
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\StaticCall(
            class: new Node\Name($this->generatedNamespace.'\\PDOPool'),
            name: new Node\Name('unique'),
            args: [
                new Node\Arg(value: $this->dsn),
                $this->username ? new Node\Arg($this->username) : new Node\Expr\ConstFetch(new Node\Name('null')),
                $this->password ? new Node\Arg($this->password) : new Node\Expr\ConstFetch(new Node\Name('null')),
                new Node\Arg(
                    value: new Node\Expr\Array_(
                        items: [
                            $this->persistentConnection ? new Node\Expr\ArrayItem(
                                value: new Node\Expr\ConstFetch(new Node\Name("{$this->persistentConnection}")),
                                key: new Node\Expr\ClassConstFetch(
                                    class: new Node\Name\FullyQualified('PDO'),
                                    name: new Node\Identifier('ATTR_PERSISTENT')
                                )
                            ) : null,
                        ],
                        attributes: [
                            'kind' => Node\Expr\Array_::KIND_SHORT,
                        ],
                    ),
                ),
            ],
        );
    }
}
