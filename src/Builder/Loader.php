<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    /** @var array<int, Node\Expr> */
    private array $beforeQueries;
    /** @var array<int, Node\Expr> */
    private array $afterQueries;

    public function __construct(
        private Node\Expr $query,
        private null|Node\Expr|Connection $connection = null,
    ) {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->beforeQueries = [];
        $this->afterQueries = [];
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

    public function withConnection(Node\Expr|Connection $connection): StepBuilderInterface
    {
        $this->connection = $connection;

        return $this;
    }

    public function withBeforeQuery(?InitializerQueries $query): self
    {
        array_push($this->beforeQueries, $query);

        return $this;
    }

    public function withBeforeQueries(?InitializerQueries ...$queries): self
    {
        array_push($this->beforeQueries, ...$queries);

        return $this;
    }

    public function withAfterQuery(?InitializerQueries $query): self
    {
        array_push($this->afterQueries, $query);

        return $this;
    }

    public function withAfterQueries(?InitializerQueries ...$queries): self
    {
        array_push($this->afterQueries, ...$queries);

        return $this;
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Stmt\Class_(
                name: null,
                subNodes: [
                    'implements' => [
                        new Node\Name\FullyQualified(name: 'Kiboko\\Contract\\Pipeline\\LoaderInterface'),
                    ],
                    'stmts' => [
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier(name: '__construct'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'params' => [
                                    new Node\Param(
                                        var: new Node\Expr\Variable('logger'),
                                        type: new Node\Name\FullyQualified(name: 'Psr\\Log\\LoggerInterface'),
                                        flags: Node\Stmt\Class_::MODIFIER_PUBLIC,
                                    ),
                                ],
                            ],
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('load'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'returnType' => new Node\Name\FullyQualified(name: 'Generator'),
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        expr: new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('input'),
                                            expr: new Node\Expr\Yield_()
                                        )
                                    ),
                                    new Node\Stmt\TryCatch(
                                        stmts: [
                                            new Node\Stmt\Expression(
                                                expr: new Node\Expr\Assign(
                                                    var: new Node\Expr\Variable('dbh'),
                                                    expr: $this->connection->getNode(),
                                                ),
                                            ),
                                            new Node\Stmt\Do_(
                                                cond: new Node\Expr\Assign(
                                                    var: new Node\Expr\Variable(name: 'input'),
                                                    expr: new Node\Expr\Yield_(
                                                        value: new Node\Expr\New_(
                                                            class: new Node\Name\FullyQualified('Kiboko\Component\Bucket\AcceptanceResultBucket'),
                                                            args: [
                                                                new Node\Arg(
                                                                    new Node\Expr\Variable('input')
                                                                ),
                                                            ],
                                                        ),
                                                    ),
                                                ),
                                                stmts: [
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
                                                ],
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
                                                    new Node\Name\FullyQualified('PDOException')
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
                                ],
                            ],
                        ),
                    ],
                ],
            ),
            args: [
                new Node\Arg(value: $this->logger ?? new Node\Expr\New_(new Node\Name\FullyQualified('Psr\\Log\\NullLogger'))),
            ],
        );
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
                            is_string($key) ? new Node\Scalar\Encapsed([new Node\Scalar\EncapsedStringPart(':'), new Node\Scalar\EncapsedStringPart($key)]) : new Node\Scalar\LNumber($key)
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
