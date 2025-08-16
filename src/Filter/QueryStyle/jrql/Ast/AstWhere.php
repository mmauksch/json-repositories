<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstWhere extends AstNode
{
    const TYPE = 'AST::WHERE';

    private ?AstExpression $expression = null;

    public function __construct(?AstExpression $expression) {
        parent::__construct(self::TYPE);
        $this->expression = $expression;
    }

    public function getExpression(): ?AstExpression
    {
        return $this->expression;
    }

}