<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Lookup implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    /** @var array<int, Node\Expr> */
    private array $beforeQueries;
    /** @var array<int, Node\Expr> */
    private array $afterQueries;

    public function __construct(
        private AlternativeLookup $alternative,
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

    private function compileAlternative(AlternativeLookup $lookup): array
    {
        return [
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    var: new Node\Expr\Variable('output'),
                    expr: new Node\Expr\Variable('input'),
                ),
            ),
            $lookup->getNode()
        ];
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
                                    new Node\Stmt\Do_(
                                        cond: new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('input'),
                                            expr: new Node\Expr\Yield_(
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
                                        stmts: $this->compileAlternative($this->alternative),
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
}
