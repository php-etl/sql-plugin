<?php

declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Lookup implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    /** @var array<int, InitializerQueries> */
    private array $beforeQueries = [];
    /** @var array<int, InitializerQueries> */
    private array $afterQueries = [];

    public function __construct(
        private readonly AlternativeLookup $alternative,
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

    /**
     * @return array<int, Node>
     */
    private function compileAlternative(AlternativeLookup $lookup): array
    {
        return [
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    var: new Node\Expr\Variable('output'),
                    expr: new Node\Expr\Variable('input'),
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    var: new Node\Expr\Variable('dbh'),
                    expr: $this->connection->getNode(),
                ),
            ),
            $lookup->getNode(),
        ];
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

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified(\Kiboko\Component\Flow\SQL\Lookup::class),
            args: [
                new Node\Arg(
                    $this->connection->getNode()
                ),
                new Node\Arg(
                    new Node\Expr\New_(
                        class: new Node\Stmt\Class_(
                            name: null,
                            subNodes: [
                                'implements' => [
                                    new Node\Name\FullyQualified(\Kiboko\Contract\Mapping\CompiledMapperInterface::class),
                                ],
                                'stmts' => [
                                    new Node\Stmt\ClassMethod(
                                        name: new Node\Identifier('__construct'),
                                        subNodes: [
                                            'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                            'stmts' => [
                                                new Node\Stmt\Expression(
                                                    expr: new Node\Expr\Assign(
                                                        var: new Node\Expr\PropertyFetch(
                                                            var: new Node\Expr\Variable('this'),
                                                            name: new Node\Identifier('logger')
                                                        ),
                                                        expr: new Node\Expr\BinaryOp\Coalesce(
                                                            left: new Node\Expr\Variable('logger'),
                                                            right: new Node\Expr\New_(
                                                                class: new Node\Name\FullyQualified(\Psr\Log\NullLogger::class)
                                                            )
                                                        )
                                                    )
                                                ),
                                            ],
                                            'params' => [
                                                new Node\Param(
                                                    var: new Node\Expr\Variable(
                                                        name: 'logger',
                                                    ),
                                                    default: new Node\Expr\ConstFetch(
                                                        name: new Node\Name(name: 'null'),
                                                    ),
                                                    type: new Node\NullableType(\Psr\Log\LoggerInterface::class),
                                                    flags: Node\Stmt\Class_::MODIFIER_PRIVATE
                                                ),
                                            ],
                                        ],
                                    ),
                                    new Node\Stmt\ClassMethod(
                                        name: new Node\Identifier('__invoke'),
                                        subNodes: [
                                            'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                            'stmts' => [
                                                ...$this->compileAlternative($this->alternative),
                                                new Node\Stmt\Return_(new Node\Expr\Variable('output')),
                                            ],
                                            'params' => [
                                                new Node\Param(
                                                    new Node\Expr\Variable(
                                                        name: 'input'
                                                    ),
                                                ),
                                                new Node\Param(
                                                    var: new Node\Expr\Variable(
                                                        name: 'output',
                                                    ),
                                                    default: new Node\Expr\ConstFetch(
                                                        name: new Node\Name(name: 'null'),
                                                    ),
                                                ),
                                            ],
                                        ],
                                    ),
                                ],
                            ],
                        ),
                    ),
                ),
                new Node\Arg(
                    value: $this->compileBeforeQueries()
                ),
                new Node\Arg(
                    value: $this->compileAfterQueries()
                ),
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
}
