<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstLimit extends AstNode
{
    const TYPE = 'AST::LIMIT';
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