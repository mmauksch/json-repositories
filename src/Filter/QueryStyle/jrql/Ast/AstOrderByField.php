<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstOrderByField extends AstNode
{
    const TYPE = 'AST::ORDER_BY_FIELD';
    private string $field;
    private AstOrderByDirection $order;

    public function __construct(string $field, AstOrderByDirection $order) {
        parent::__construct(self::TYPE);
        $this->field = $field;
        $this->order = $order;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOrder(): AstOrderByDirection
    {
        return $this->order;
    }
}