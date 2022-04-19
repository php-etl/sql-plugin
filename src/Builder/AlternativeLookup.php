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

    public function addParam(Parameter $parameter): StepBuilderInterface
    {
        $this->parameters[] = $parameter;

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

        /** @var Parameter $parameter */
        foreach ($this->parameters as $parameter) {
            $output[] = new Node\Stmt\Expression(
                expr: new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('stmt'),
                    name: new Node\Identifier('bindParam'),
                    args: array_filter([
                        new Node\Arg(
                            is_string($parameter->key) ? new Node\Scalar\Encapsed(
                                [
                                    new Node\Scalar\EncapsedStringPart(':'),
                                    new Node\Scalar\EncapsedStringPart($parameter->key)
                                ]
                            ) : new Node\Scalar\LNumber($parameter->key)
                        ),
                        new Node\Arg(
                            $parameter->value
                        ),
                        $parameter->type !== null ? new Node\Arg(
                            value: new Node\Expr\ClassConstFetch(
                                class: new Node\Name\FullyQualified(name: 'PDO'),
                                name: $parameter->type === 'boolean' ? new Node\Identifier(name: 'PARAM_BOOL') : new Node\Identifier(name: 'PARAM_INT')
                            )
                        ) : null,
                    ]),
                )
            );
        }

        return $output;
    }
}
