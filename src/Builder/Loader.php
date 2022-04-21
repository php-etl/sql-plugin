<?php declare(strict_types=1);

namespace Kiboko\Plugin\SQL\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use Kiboko\Plugin\SQL\Builder\DTO\Parameter;
use PhpParser\Node;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    /** @var array<int, Node\Expr> */
    private array $beforeQueries;
    /** @var array<int, Node\Expr> */
    private array $afterQueries;
    private array $parameters;

    public function __construct(
        private Node\Expr $query,
        private null|Node\Expr|Connection $connection = null,
    ) {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->beforeQueries = [];
        $this->afterQueries = [];
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

    public function withConnection(Node\Expr|Connection $connection): StepBuilderInterface
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

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\SQL\\Loader'),
            args: [
                new Node\Arg(
                    value: $this->connection->getNode()
                ),
                new Node\Arg(
                    value: $this->query
                ),
                count($this->parameters) > 0
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
                                ...$this->compileParameters()
                            ],
                        ],
                    ))
                    : new Node\Expr\ConstFetch(new Node\Name('null')),
                count($this->beforeQueries) > 0
                    ? new Node\Arg(value: $this->compileBeforeQueries())
                    : new Node\Expr\Array_(attributes: [
                        'kind' => Node\Expr\Array_::KIND_SHORT
                    ]),
                count($this->afterQueries) > 0
                    ? new Node\Arg(value: $this->compileAfterQueries())
                    : new Node\Expr\Array_(attributes: [
                        'kind' => Node\Expr\Array_::KIND_SHORT
                    ]),
                new Node\Arg(value: $this->logger ?? new Node\Expr\New_(new Node\Name\FullyQualified('Psr\\Log\\NullLogger'))),
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

    public function compileParameters(): iterable
    {
        foreach ($this->parameters as $key => $parameter) {
            yield new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('statement'),
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
