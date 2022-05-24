<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class AlternativeLoader implements StepBuilderInterface
{
    /** @var array<array-key, array{value: Node\Expr, type: string}> */
    private array $parameters;

    public function __construct(
        private Node\Expr $query
    ) {
        $this->parameters = [];
    }

    public function withLogger(Node\Expr $logger): StepBuilderInterface
    {
        return $this;
    }

    public function withRejection(Node\Expr $rejection): StepBuilderInterface
    {
        return $this;
    }

    public function withState(Node\Expr $state): StepBuilderInterface
    {
        return $this;
    }

    public function addStringParam(int|string $key, Node\Expr $param): StepBuilderInterface
    {
        $this->parameters[$key] = [
            'value' => $param,
            'type' => 'string',
        ];

        return $this;
    }

    public function addIntegerParam(int|string $key, Node\Expr $param): StepBuilderInterface
    {
        $this->parameters[$key] = [
            'value' => $param,
            'type' => 'integer',
        ];

        return $this;
    }

    public function addBooleanParam(int|string $key, Node\Expr $param): StepBuilderInterface
    {
        $this->parameters[$key] = [
            'value' => $param,
            'type' => 'boolean',
        ];

        return $this;
    }

    public function addDateParam(int|string $key, Node\Expr $param): self
    {
        $this->parameters[$key] = [
            'value' => $param,
            'type' => 'date',
        ];

        return $this;
    }

    public function addDateTimeParam(int|string $key, Node\Expr $param): self
    {
        $this->parameters[$key] = [
            'value' => $param,
            'type' => 'datetime',
        ];

        return $this;
    }

    public function addJSONParam(int|string $key, Node\Expr $param): self
    {
        $this->parameters[$key] = [
            'value' => $param,
            'type' => 'json',
        ];

        return $this;
    }

    public function addBinaryParam(int|string $key, Node\Expr $param): self
    {
        $this->parameters[$key] = [
            'value' => $param,
            'type' => 'binary',
        ];

        return $this;
    }

    public function getNode(): Node
    {
        return new Node\Stmt\Expression(
            expr: new Node\Expr\FuncCall(
                name: new Node\Expr\Closure([
                    'params' => [
                        new Node\Param(
                            var: new Node\Expr\Variable('input'),
                        ),
                    ],
                    'stmts' => [
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\Assign(
                                var: new Node\Expr\Variable('statement'),
                                expr: new Node\Expr\MethodCall(
                                    var: new Node\Expr\Variable('connection'),
                                    name: new Node\Identifier('prepare'),
                                    args: [
                                        new Node\Arg(
                                            value: $this->query
                                        ),
                                    ]
                                )
                            )
                        ),
                        ...$this->compileParameters(),
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\MethodCall(
                                var: new Node\Expr\Variable('statement'),
                                name: new Node\Identifier('execute'),
                            )
                        ),
                    ],
                    'uses' => [
                        new Node\Expr\Variable('connection'),
                    ],
                ]),
                args: [
                    new Node\Arg(
                        value: new Node\Expr\Variable('input')
                    ),
                ]
            )
        );
    }

    public function compileParameters(): iterable
    {
        foreach ($this->parameters as $key => $parameter) {
            yield match ($parameter['type']) {
                'datetime' => new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        var: new Node\Expr\Variable('statement'),
                        name: new Node\Identifier('bindValue'),
                        args: [
                            new Node\Arg(
                                \is_string($key) ? new Node\Scalar\Encapsed(
                                    [
                                        new Node\Scalar\EncapsedStringPart(':'),
                                        new Node\Scalar\EncapsedStringPart($key),
                                    ]
                                ) : new Node\Scalar\LNumber($key)
                            ),
                            new Node\Arg(
                                value: new Node\Expr\MethodCall(
                                    var: $parameter['value'],
                                    name: new Node\Name('format'),
                                    args: [
                                        new Node\Arg(
                                            value: new Node\Scalar\String_('YYYY-MM-DD HH:MI:SS')
                                        ),
                                    ],
                                ),
                            ),
                            $this->compileParameterType($parameter),
                        ],
                    ),
                ),
                'date' => new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        var: new Node\Expr\Variable('statement'),
                        name: new Node\Identifier('bindValue'),
                        args: [
                            new Node\Arg(
                                \is_string($key) ? new Node\Scalar\Encapsed(
                                    [
                                        new Node\Scalar\EncapsedStringPart(':'),
                                        new Node\Scalar\EncapsedStringPart($key),
                                    ]
                                ) : new Node\Scalar\LNumber($key)
                            ),
                            new Node\Arg(
                                value: new Node\Expr\MethodCall(
                                    var: $parameter['value'],
                                    name: new Node\Name('format'),
                                    args: [
                                        new Node\Arg(
                                            value: new Node\Scalar\String_('YYYY-MM-DD')
                                        ),
                                    ],
                                ),
                            ),
                            $this->compileParameterType($parameter),
                        ],
                    ),
                ),
                'json' => new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        var: new Node\Expr\Variable('statement'),
                        name: new Node\Identifier('bindValue'),
                        args: [
                            new Node\Arg(
                                \is_string($key) ? new Node\Scalar\Encapsed(
                                    [
                                        new Node\Scalar\EncapsedStringPart(':'),
                                        new Node\Scalar\EncapsedStringPart($key),
                                    ]
                                ) : new Node\Scalar\LNumber($key)
                            ),
                            new Node\Arg(
                                new Node\Expr\FuncCall(
                                    name: new Node\Name('json_encode'),
                                    args: [
                                        new Node\Arg(
                                            value: $parameter['value']
                                        ),
                                    ],
                                ),
                            ),
                            $this->compileParameterType($parameter),
                        ],
                    ),
                ),
                default => new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        var: new Node\Expr\Variable('statement'),
                        name: new Node\Identifier('bindValue'),
                        args: [
                            new Node\Arg(
                                \is_string($key) ? new Node\Scalar\Encapsed(
                                    [
                                        new Node\Scalar\EncapsedStringPart(':'),
                                        new Node\Scalar\EncapsedStringPart($key),
                                    ]
                                ) : new Node\Scalar\LNumber($key)
                            ),
                            new Node\Arg(
                                $parameter['value']
                            ),
                            $this->compileParameterType($parameter),
                        ],
                    ),
                ),
            };
        }
    }

    private function compileParameterType(array $parameter): Node\Arg
    {
        return match ($parameter['type']) {
            'integer' => new Node\Arg(
                value: new Node\Expr\ClassConstFetch(
                    class: new Node\Name\FullyQualified(name: 'PDO'),
                    name: new Node\Identifier(name: 'PARAM_INT')
                )
            ),
            'boolean' => new Node\Arg(
                value: new Node\Expr\ClassConstFetch(
                    class: new Node\Name\FullyQualified(name: 'PDO'),
                    name: new Node\Identifier(name: 'PARAM_BOOL')
                )
            ),
            'binary' => new Node\Arg(
                value: new Node\Expr\ClassConstFetch(
                    class: new Node\Name\FullyQualified(name: 'PDO'),
                    name: new Node\Identifier(name: 'PARAM_LOB')
                )
            ),
            default => new Node\Arg(
                value: new Node\Expr\ClassConstFetch(
                    class: new Node\Name\FullyQualified(name: 'PDO'),
                    name: new Node\Identifier(name: 'PARAM_STR')
                )
            ),
        };
    }
}
