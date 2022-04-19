<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use Kiboko\Plugin\SQL\Builder\DTO\Parameter;
use PhpParser\Node;

final class AlternativeLoader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    /** @var array<Node\Expr> */
    private array $parameters;

    public function __construct(
        private Node\Expr $query
    ) {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->parameters = [];
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

    public function getNode(): Node
    {
        return new Node\Stmt\Expression(
            expr: new Node\Expr\FuncCall(
                name: new Node\Expr\Closure([
                    'params' => [
                        new Node\Param(
                            var: new Node\Expr\Variable('input'),
                        )
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
                                        )
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
                        )
                    ],
                    'uses' => [
                        new Node\Expr\Variable('connection')
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
        /** @var Parameter $parameter */
        foreach ($this->parameters as $parameter) {
            yield new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('statement'),
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
    }
}
