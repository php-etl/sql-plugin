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
            class: new Node\Name\FullyQualified('Kiboko\Component\Flow\SQL\Lookup'),
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
                                    new Node\Name\FullyQualified('Kiboko\Contract\Mapping\CompiledMapperInterface')
                                ],
                                'stmts' => [
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
                ...$output
            ],
            attributes: [
                'kind' => Node\Expr\Array_::KIND_SHORT
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
                ...$output
            ],
            attributes: [
                'kind' => Node\Expr\Array_::KIND_SHORT
            ]
        );
    }
}
