<?php

namespace Kiboko\Plugin\SQL\Builder\DTO;

use PhpParser\Node;

class Parameter
{
    public function __construct(
        public string|int $key,
        public Node\Expr $value,
        public ?string $type = null,
    ){}
}
