<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class ConditionalLoader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    /** @var array<Node\Expr> */
    private iterable $alternatives;
    /** @var array<int, Node\Expr> */
    private array $beforeQueries;
    /** @var array<int, Node\Expr> */
    private array $afterQueries;

    public function __construct(
        private null|Node\Expr|Connection $connection = null,
    ) {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->alternatives = [];
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

    public function addAlternative(Node\Expr $condition, AlternativeLoader $lookup): self
    {
        $this->alternatives[] = [$condition, $lookup];

        return $this;
    }

    /**
     * @return array<int, Node>
     */
    private function compileAlternative(AlternativeLoader $loader): array
    {
        return [
            $loader->getNode(),
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
        $alternatives = $this->alternatives;
        [$condition, $alternative] = array_shift($alternatives);

        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\Component\Flow\SQL\ConditionalLoader'),
            args: [
                new Node\Arg(
                    value: $this->connection->getNode()
                ),
                new Node\Arg(
                    value: new Node\Expr\Closure(
                        subNodes: [
                            'params' => [
                                new Node\Param(
                                    var: new Node\Expr\Variable('input')
                                ),
                                new Node\Param(
                                    var: new Node\Expr\Variable('connection')
                                ),
                            ],
                            'stmts' => [
                                new Node\Stmt\If_(
                                    cond: $condition,
                                    subNodes: [
                                        'stmts' => [
                                            ...$this->compileAlternative($alternative)
                                        ],
                                        'elseifs' => array_map(
                                            fn (Node\Expr $condition, AlternativeLoader $loader)
                                                => new Node\Stmt\ElseIf_(
                                                    cond: $condition,
                                                    stmts: [
                                                        ...$this->compileAlternative($loader)
                                                    ],
                                                ),
                                            array_column($alternatives, 0),
                                            array_column($alternatives, 1)
                                        ),
                                    ],
                                ),
                                new Node\Stmt\Return_(
                                    expr: new Node\Expr\Variable('input')
                                ),
                            ],
                        ],
                    ),
                ),
                $this->beforeQueries ? new Node\Arg(
                    value: $this->compileBeforeQueries()
                ) : new Node\Expr\Array_(
                    attributes: [
                        'kind' => Node\Expr\Array_::KIND_SHORT
                    ]
                ),
                $this->afterQueries ? new Node\Arg(
                    value: $this->compileAfterQueries()
                ): new Node\Expr\Array_(
                    attributes: [
                        'kind' => Node\Expr\Array_::KIND_SHORT
                    ],
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
