<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    /** @var array<int, InitializerQueries> */
    private array $beforeQueries = [];
    /** @var array<int, InitializerQueries> */
    private array $afterQueries = [];
    /** @var array<array-key, array{value: Node\Expr, type: string}> */
    private array $parameters = [];
    /** @var array<array-key, array{value: Node\Expr, type: string}> */
    private array $parametersLists = [];

    public function __construct(
        private readonly Node\Expr $query,
        private null|Node\Expr|ConnectionBuilderInterface $connection = null
    ) {
    }

    public function withLogger(Node\Expr $logger): StepBuilderInterface
    {
        $this->logger = $logger;

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

    public function withConnection(Node\Expr|ConnectionBuilderInterface $connection): StepBuilderInterface
    {
        $this->connection = $connection;

        return $this;
    }

    public function withBeforeQuery(InitializerQueries $query): self
    {
        $this->beforeQueries[] = $query;

        return $this;
    }

    public function withBeforeQueries(InitializerQueries ...$queries): self
    {
        array_push($this->beforeQueries, ...$queries);

        return $this;
    }

    public function withAfterQuery(InitializerQueries $query): self
    {
        $this->afterQueries[] = $query;

        return $this;
    }

    public function withAfterQueries(InitializerQueries ...$queries): self
    {
        array_push($this->afterQueries, ...$queries);

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
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified(\Kiboko\Component\Flow\SQL\Loader::class),
            args: [
                new Node\Arg(
                    value: $this->connection->getNode()
                ),
                new Node\Arg(
                    value: $this->query
                ),
                \count($this->parameters) > 0
                    ? new Node\Arg(value: new Node\Expr\Closure(
                        subNodes: [
                            'params' => [
                                new Node\Param(
                                    var: new Node\Expr\Variable('statement'),
                                    type: new Node\Name\FullyQualified('PDOStatement')
                                ),
                                new Node\Param(
                                    var: new Node\Expr\Variable('input'),
                                ),
                            ],
                            'stmts' => [
                                ...$this->walkParameters(),
                            ],
                        ],
                    ))
                    : new Node\Expr\ConstFetch(new Node\Name('null')),
                \count($this->beforeQueries) > 0
                    ? new Node\Arg(value: $this->compileBeforeQueries())
                    : new Node\Expr\Array_(attributes: [
                        'kind' => Node\Expr\Array_::KIND_SHORT,
                    ]),
                \count($this->afterQueries) > 0
                    ? new Node\Arg(value: $this->compileAfterQueries())
                    : new Node\Expr\Array_(attributes: [
                        'kind' => Node\Expr\Array_::KIND_SHORT,
                    ]),
                new Node\Arg(value: $this->logger ?? new Node\Expr\New_(new Node\Name\FullyQualified(\Psr\Log\NullLogger::class))),
            ],
        );
    }

    public function compileBeforeQueries(): Node\Expr
    {
        $output = [];

        /**
         * @var InitializerQueries $beforeQuery
         */
        foreach ($this->beforeQueries as $beforeQuery) {
            $output[] = new Node\Expr\ArrayItem(
                $beforeQuery->getNode()
            );
        }

        return new Node\Expr\Array_(
            items: [
                ...$output,
            ],
            attributes: [
                'kind' => Node\Expr\Array_::KIND_SHORT,
            ]
        );
    }

    public function compileAfterQueries(): Node\Expr
    {
        $output = [];

        /**
         * @var InitializerQueries $afterQuery
         */
        foreach ($this->afterQueries as $afterQuery) {
            $output[] = new Node\Expr\ArrayItem(
                $afterQuery->getNode()
            );
        }

        return new Node\Expr\Array_(
            items: [
                ...$output,
            ],
            attributes: [
                'kind' => Node\Expr\Array_::KIND_SHORT,
            ]
        );
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

    public function compileParameter(int|string|Node\Arg $key, array $parameter): Node\Stmt\Expression
    {
        return match ($parameter['type']) {
            'datetime' => new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('statement'),
                    name: new Node\Identifier('bindValue'),
                    args: [
                        $this->compileParameterKey($key),
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
                        $this->compileParameterKey($key),
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
                        $this->compileParameterKey($key),
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
