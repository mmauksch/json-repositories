<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstOperatorExpression extends AstExpression
{
    const TYPE = 'AST::CONDITION';

    private string $field;
    private AstOperatior $operator;
    private mixed $value;

    public function __construct(string $field, AstOperatior $operator, mixed $value) {
        parent::__construct(self::TYPE);
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getField(): string {
        return $this->field;
    }

    public function getOperator(): AstOperatior {
        return $this->operator;
    }

    public function getValue(): mixed {
        return $this->value;
    }


}