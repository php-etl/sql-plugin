<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Component\SatelliteToolbox\Builder\IsolatedValueAppendingBuilder;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use Kiboko\Plugin\SQL\Builder\DTO\Parameter;
use PhpParser\Builder;
use PhpParser\Node;

final class AlternativeLookup implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    /** @var array<Node\Expr> */
    private array $parameters;
    private ?Builder $merge;

    public function __construct(private Node\Expr $query)
    {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->parameters = [];
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
            [
                ...array_filter(
                    [
                        $this->getAlternativeLookupNode(),
                        $this->merge?->getNode(),
                        new Node\Stmt\Return_(
                            new Node\Expr\Variable('output')
                        ),
                    ]
                )
            ],
            new Node\Expr\Variable('dbh'),
        ))->getNode();
    }

    public function getAlternativeLookupNode() : Node
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
                                        new Node\Arg($this->query)
                                    ],
                                ),
                            ),
                        ),
                        ...$this->compileParameters(),
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
                        )
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
            new Node\Expr\Variable('dbh'),
        ))->getNode();
    }

    /**
     * @return array<int, Node\Stmt\Expression>
     */
    public function compileParameters(): array
    {
        $output = [];

        foreach ($this->parameters as $key => $parameter) {
            $output[] = new Node\Stmt\Expression(
                expr: new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('stmt'),
                    name: new Node\Identifier('bindParam'),
                    args: array_filter([
                        new Node\Arg(
                            is_string($key) ? new Node\Scalar\Encapsed(
                                [
                                    new Node\Scalar\EncapsedStringPart(':'),
                                    new Node\Scalar\EncapsedStringPart($key)
                                ]
                            ) : new Node\Scalar\LNumber($key)
                        ),
                        new Node\Arg(
                            $parameter["value"]
                        ),
                        $this->compileParameterType($parameter)
                    ]),
                )
            );
        }

        return $output;
    }

    private function compileParameterType(array $parameter): ?Node\Arg
    {
        return match ($parameter["type"]) {
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
            'string' => new Node\Arg(
                value: new Node\Expr\ClassConstFetch(
                    class: new Node\Name\FullyQualified(name: 'PDO'),
                    name: new Node\Identifier(name: 'PARAM_STR')
                )
            ),
            default => null
        };
    }
}
