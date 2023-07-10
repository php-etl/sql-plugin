<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Component\SatelliteToolbox\Builder\IsolatedValueAppendingBuilder;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Builder;
use PhpParser\Node;

final class AlternativeLookup implements StepBuilderInterface
{
    /** @var array<array-key, array{value: Node\Expr, type: string}> */
    private array $parameters = [];
    /** @var array<array-key, array{value: Node\Expr, type: string}> */
    private array $parametersLists = [];
    private ?Builder $merge = null;

    public function __construct(private readonly Node\Expr $query)
    {
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

    public function withMerge(Builder $merge): self
    {
        $this->merge = $merge;

        return $this;
    }

    public function addStringParamList(int|string $key, Node\Expr $param): StepBuilderInterface
    {
        $this->parametersLists[$key] = [
            'value' => $param,
            'type' => 'string',
        ];

        return $this;
    }

    public function addIntegerParamList(int|string $key, Node\Expr $param): StepBuilderInterface
    {
        $this->parametersLists[$key] = [
            'value' => $param,
            'type' => 'integer',
        ];

        return $this;
    }

    public function addBooleanParamList(int|string $key, Node\Expr $param): StepBuilderInterface
    {
        $this->parametersLists[$key] = [
            'value' => $param,
            'type' => 'boolean',
        ];

        return $this;
    }

    public function addDateParamList(int|string $key, Node\Expr $param): self
    {
        $this->parametersLists[$key] = [
            'value' => $param,
            'type' => 'date',
        ];

        return $this;
    }

    public function addDateTimeParamList(int|string $key, Node\Expr $param): self
    {
        $this->parametersLists[$key] = [
            'value' => $param,
            'type' => 'datetime',
        ];

        return $this;
    }

    public function addJSONParamList(int|string $key, Node\Expr $param): self
    {
        $this->parametersLists[$key] = [
            'value' => $param,
            'type' => 'json',
        ];

        return $this;
    }

    public function addBinaryParamList(int|string $key, Node\Expr $param): self
    {
        $this->parametersLists[$key] = [
            'value' => $param,
            'type' => 'binary',
        ];

        return $this;
    }

    public function getNode(): Node
    {
        return (new IsolatedValueAppendingBuilder(
            new Node\Expr\Variable('input'),
            new Node\Expr\Variable('output'),
            [
                ...array_filter(
                    [
                        $this->getAlternativeLookupNode(),
                        new Node\Stmt\If_(
                            cond: new Node\Expr\Variable('lookup'),
                            subNodes: [
                                'stmts' => [
                                    $this->merge?->getNode(),
                                ],
                            ]
                        ),
                        new Node\Stmt\Return_(
                            new Node\Expr\Variable('output')
                        ),
                    ]
                ),
            ],
            new Node\Expr\Variable('dbh'),
        ))->getNode();
    }

    public function getAlternativeLookupNode(): Node
    {
        return (new IsolatedValueAppendingBuilder(
            new Node\Expr\Variable('input'),
            new Node\Expr\Variable('lookup'),
            [
                new Node\Stmt\TryCatch(
                    stmts: [
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\Assign(
                                var: new Node\Expr\Variable('stmt'),
                                expr: new Node\Expr\MethodCall(
                                    var: new Node\Expr\Variable('dbh'),
                                    name: new Node\Identifier('prepare'),
                                    args: [
                                        new Node\Arg($this->query),
                                    ],
                                ),
                            ),
                        ),
                        ...$this->walkParameters(),
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\MethodCall(
                                var: new Node\Expr\Variable('stmt'),
                                name: new Node\Identifier('execute')
                            ),
                        ),
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\Assign(
                                var: new Node\Expr\Variable('data'),
                                expr: new Node\Expr\MethodCall(
                                    var: new Node\Expr\Variable('stmt'),
                                    name: new Node\Identifier('fetch'),
                                    args: [
                                        new Node\Arg(
                                            new Node\Expr\ClassConstFetch(
                                                class: new Node\Name\FullyQualified('PDO'),
                                                name: new Node\Identifier('FETCH_NAMED')
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
                        new Node\Stmt\Return_(
                            expr: new Node\Expr\Variable('data')
                        ),
                    ],
                    catches: [
                        new Node\Stmt\Catch_(
                            types: [
                                new Node\Name\FullyQualified('PDOException'),
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
            new Node\Expr\Variable('dbh'),
        ))->getNode();
    }

    public function walkParameters(): iterable
    {
        foreach ($this->parameters as $key => $parameter) {
            yield $this->compileParameter($key, $parameter);
        }

        foreach ($this->parametersLists as $key => $parameter) {
            yield new Node\Stmt\Foreach_(
                expr: $parameter['value'],
                valueVar: new Node\Expr\Variable('value'),
                subNodes: [
                    'keyVar' => new Node\Expr\Variable('key'),
                    'stmts' => [
                        $this->compileParameter(
                            new Node\Arg(
                                new Node\Expr\BinaryOp\Concat(
                                    new Node\Scalar\String_($key.'_'),
                                    new Node\Expr\Variable('key'),
                                )
                            ),
                            [
                                'type' => $parameter['type'],
                                'value' => new Node\Expr\Variable('value'),
                            ]
                        ),
                    ],
                ]
            );
        }
    }

    private function compileParameter(int|string|Node\Arg $key, array $parameter): Node\Stmt\Expression
    {
        return match ($parameter['type']) {
            'datetime' => new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('stmt'),
                    name: new Node\Identifier('bindValue'),
                    args: [
                        $this->compileParameterKey($key),
                        new Node\Arg(
                            value: new Node\Expr\StaticCall(
                                class: new Node\Name('DateTimeImmutable'),
                                name: new Node\Name('createFromFormat'),
                                args: [
                                    new Node\Arg(
                                        value: new Node\Scalar\String_('YYYY-MM-DD HH:MI:SS')
                                    ),
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
            'date' => new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('stmt'),
                    name: new Node\Identifier('bindValue'),
                    args: [
                        $this->compileParameterKey($key),
                        new Node\Arg(
                            value: new Node\Expr\StaticCall(
                                class: new Node\Name('DateTimeImmutable'),
                                name: new Node\Name('createFromFormat'),
                                args: [
                                    new Node\Arg(
                                        value: new Node\Scalar\String_('YYYY-MM-DD')
                                    ),
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
            'json' => new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('stmt'),
                    name: new Node\Identifier('bindValue'),
                    args: [
                        $this->compileParameterKey($key),
                        new Node\Arg(
                            new Node\Expr\FuncCall(
                                name: new Node\Name('json_decode'),
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
                    var: new Node\Expr\Variable('stmt'),
                    name: new Node\Identifier('bindValue'),
                    args: [
                        $this->compileParameterKey($key),
                        new Node\Arg(
                            $parameter['value']
                        ),
                        $this->compileParameterType($parameter),
                    ],
                ),
            ),
        };
    }

    private function compileParameterKey(int|string|Node\Arg $key): Node\Arg
    {
        if (\is_string($key)) {
            return new Node\Arg(
                new Node\Scalar\Encapsed([
                    new Node\Scalar\EncapsedStringPart(':'),
                    new Node\Scalar\EncapsedStringPart($key),
                ])
            );
        }
        if ($key instanceof Node\Arg) {
            return $key;
        }

        return new Node\Arg(
            new Node\Scalar\LNumber($key)
        );
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
            )
        };
    }
}
