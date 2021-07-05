<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Extractor implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function __construct(private Node\Expr $query, private Node\Expr $dsn, private ?Node\Expr $username = null, private ?Node\Expr $password = null)
    {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
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

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Stmt\Class_(
                name: null,
                subNodes: [
                    'implements' => [
                        new Node\Name\FullyQualified(name: 'Kiboko\\Contract\\Pipeline\\ExtractorInterface'),
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
                            name: new Node\Identifier(name: 'extract'),
                            subNodes: [
                                 'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                 'params' => [],
                                 'returnType' => new Node\Name(name: 'iterable'),
                                 'stmts' => [
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
                                                    var: new Node\Expr\Variable('output'),
                                                    expr: new Node\Expr\Array_(
                                                        attributes: [
                                                            'kind' => Node\Expr\Array_::KIND_SHORT
                                                        ]
                                                    )
                                                )
                                            ),
                                            new Node\Stmt\Foreach_(
                                                expr: new Node\Expr\MethodCall(
                                                    var: new Node\Expr\Variable('dbh'),
                                                    name: new Node\Name('query'),
                                                    args: [
                                                        new Node\Arg($this->query)
                                                    ]
                                                ),
                                                valueVar: new Node\Expr\Variable('row'),
                                                subNodes: [
                                                    'stmts' => [
                                                        new Node\Stmt\Expression(
                                                            new Node\Expr\FuncCall(
                                                                name: new Node\Name('array_push'),
                                                                args: [
                                                                    new Node\Arg(
                                                                        new Node\Expr\Variable('output')
                                                                    ),
                                                                    new Node\Arg(
                                                                        new Node\Expr\Variable('row')
                                                                    ),
                                                                ]
                                                            )
                                                        )
                                                    ]
                                                ]
                                            ),
                                            new Node\Stmt\Return_(
                                                expr: new Node\Expr\Variable('output')
                                            )
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
                                    )
                                 ]
                             ]
                         ),
                    ],
                ],
            ),
            args: [
                new Node\Arg(value: $this->logger ?? new Node\Expr\New_(new Node\Name\FullyQualified('Psr\\Log\\NullLogger'))),
            ]
        );
    }
}
