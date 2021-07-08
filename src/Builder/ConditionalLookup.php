<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class ConditionalLookup implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    private iterable $alternatives;

    public function __construct()
    {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->alternatives = [];
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

    public function addAlternative(Node\Expr $condition, AlternativeLookup $lookup): self
    {
        $this->alternatives[] = [$condition, $lookup];

        return $this;
    }

    private function compileAlternative(AlternativeLookup $lookup): array
    {
        return [
            $lookup->getNode(),
        ];
    }

    private function getNodeAlternatives(): Node
    {
        $alternatives = $this->alternatives;
        [$condition, $alternative] = array_shift($alternatives);

        return new Node\Stmt\Do_(
            cond: new Node\Expr\Assign(
                var: new Node\Expr\Variable('input'),
                expr: new Node\Expr\Yield_(
                    value: new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('Kiboko\Component\Bucket\AcceptanceResultBucket'),
                        args: [
                           new Node\Arg(
                               value: new Node\Expr\Variable('output')
                           )
                        ],
                    ),
                ),
            ),
            stmts: array_filter([
                new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        var: new Node\Expr\Variable('output'),
                        expr:new Node\Expr\Variable('input'),
                    ),
                ),
                new Node\Stmt\If_(
                    cond: $condition,
                    subNodes: [
                        'stmts' => [
                            ...$this->compileAlternative($alternative),
                        ],
                        'elseifs' => array_map(
                            fn (Node\Expr $condition, AlternativeLookup $lookup)
                                => new Node\Stmt\ElseIf_(
                                    cond: $condition,
                                    stmts: $this->compileAlternative($lookup),
                                ),
                            array_column($alternatives, 0),
                            array_column($alternatives, 1)
                        ),
                        'else' => new Node\Stmt\Else_(
                            stmts: [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Yield_(
                                        value: new Node\Expr\New_(
                                            class: new Node\Name\FullyQualified('Kiboko\Component\Bucket\AcceptanceResultBucket'),
                                            args: [
                                               new Node\Arg(
                                                   value: new Node\Expr\Variable('output')
                                               ),
                                            ],
                                        ),
                                    ),
                                ),
                            ],
                        ),
                    ],
                ),
            ]),
        );
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Stmt\Class_(
                name: null,
                subNodes: [
                    'implements' => [
                        new Node\Name\FullyQualified(name: 'Kiboko\\Contract\\Pipeline\\TransformerInterface'),
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
                            name: new Node\Identifier('transform'),
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
                                    $this->getNodeAlternatives(),
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
}
