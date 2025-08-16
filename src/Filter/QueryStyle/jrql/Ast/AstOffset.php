<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstOffset extends AstNode
{
    const TYPE = 'AST::OFFSET';
    private int $value;

    public function __construct(int $value) {
        parent::__construct(self::TYPE);
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}