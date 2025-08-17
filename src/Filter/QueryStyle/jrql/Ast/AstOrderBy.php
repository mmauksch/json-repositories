<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstOrderBy extends AstNode
{

    const TYPE = 'AST::ORDER_BY';

    /** @var AstOrderByField[] */
    private array $fields;

    public function __construct(array $fields) {
        parent::__construct(self::TYPE);
        $this->fields = $fields;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

}