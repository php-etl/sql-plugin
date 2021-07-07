<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Component\SatelliteToolbox\Builder\IsolatedValueAppendingBuilder;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Builder;
use PhpParser\Node;

final class AlternativeLookup implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    private array $params;
    private ?Builder $merge;

    public function __construct(private Node\Expr $query, private Node\Expr $dsn, private ?Node\Expr $username = null, private ?Node\Expr $password = null)
    {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->params = [];
        $this->merge = null;
    }

    public function withLogger(Node\Expr $logger): StepBuilderInterface
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): StepBuilderInterface
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): StepBuilderInterface
    {
        $this->state = $state;

        return $this;
    }

    public function withUsername(Node\Expr $username): StepBuilderInterface
    {
        $this->username = $username;

        return $this;
    }

    public function withPassword(Node\Expr $password): StepBuilderInterface
    {
        $this->password = $password;

        return $this;
    }

    public function addParam(int|string $key, Node\Expr $param): StepBuilderInterface
    {
        $this->params[$key] = $param;

        return $this;
    }

    public function withMerge(Builder $merge): self
    {
        $this->merge = $merge;

        return $this;
    }

    public function getNode(): Node
    {
        return (new IsolatedValueAppendingBuilder(
            new Node\Expr\Variable('input'),
            new Node\Expr\Variable('output'),
            array_filter([
                new Node\Stmt\TryCatch(
                    stmts: [
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\Assign(
                                var: new Node\Expr\Variable('dbh'),
                                expr: new Node\Expr\New_(
                                    class: new Node\Name\FullyQualified('PDO'),
                                    args: [
                                        new Node\Arg($this->dsn),
                                        $this->username ? new Node\Arg($this->username) : new Node\Expr\ConstFetch(new Node\Name('null')),
                                        $this->password ? new Node\Arg($this->password) : new Node\Expr\ConstFetch(new Node\Name('null'))
                                    ],
                                ),
                            ),
                        ),
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\Assign(
                                var: new Node\Expr\Variable('stmt'),
                                expr: new Node\Expr\MethodCall(
                                    var: new Node\Expr\Variable('dbh'),
                                    name: new Node\Name('prepare'),
                                    args: [
                                        new Node\Arg($this->query)
                                    ],
                                ),
                            ),
                        ),
                        ...$this->compileParams(),
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\MethodCall(
                                var: new Node\Expr\Variable('stmt'),
                                name: new Node\Name('execute')
                            ),
                        ),
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\Assign(
                                var: new Node\Expr\Variable('lookup'),
                                expr: new Node\Expr\MethodCall(
                                    var: new Node\Expr\Variable('stmt'),
                                    name: new Node\Name('fetchAll'),
                                    args: [
                                        new Node\Arg(
                                            new Node\Expr\ClassConstFetch(
                                                class: new Node\Name\FullyQualified('PDO'),
                                                name: new Node\Name('FETCH_NAMED')
                                            ),
                                        ),
                                    ],
                                ),
                            ),
                        ),
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\Assign(
                                var: new Node\Expr\Variable('dbh'),
                                expr: new Node\Expr\ConstFetch(
                                    name: new Node\Name('null')
                                ),
                            ),
                        ),
                    ],
                    catches: [
                        new Node\Stmt\Catch_(
                            types: [
                                new Node\Name('PDOException')
                            ],
                            var: new Node\Expr\Variable('exception'),
                            stmts: [
                                new Node\Stmt\Expression(
                                    expr: new Node\Expr\MethodCall(
                                        var: new Node\Expr\PropertyFetch(
                                            var: new Node\Expr\Variable('this'),
                                            name: 'logger',
                                        ),
                                        name: new Node\Identifier('critical'),
                                        args: [
                                            new Node\Arg(
                                                value: new Node\Expr\MethodCall(
                                                    var: new Node\Expr\Variable('exception'),
                                                    name: new Node\Identifier('getMessage'),
                                                ),
                                            ),
                                            new Node\Arg(
                                                value: new Node\Expr\Array_(
                                                    items: [
                                                        new Node\Expr\ArrayItem(
                                                            value: new Node\Expr\Variable('exception'),
                                                            key: new Node\Scalar\String_('exception'),
                                                        ),
                                                    ],
                                                    attributes: [
                                                        'kind' => Node\Expr\Array_::KIND_SHORT,
                                                    ],
                                                ),
                                            ),
                                        ]
                                    ),
                                ),
                            ],
                        ),
                    ],
                ),
                $this->merge?->getNode(),
                new Node\Stmt\Return_(
                    new Node\Expr\Variable('output')
                ),
            ])
        ))->getNode();
    }

    public function compileParams(): array
    {
        $output = [];

        foreach ($this->params as $key => $param) {
            $output[] = new Node\Stmt\Expression(
                expr: new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('stmt'),
                    name: new Node\Name('bindParam'),
                    args: [
                        new Node\Arg(
                            is_string($key) ? new Node\Scalar\String_($key) : new Node\Scalar\LNumber($key)
                        ),
                        new Node\Arg(
                            $param
                        )
                    ],
                ),
            );
        }

        return $output;
    }
}
