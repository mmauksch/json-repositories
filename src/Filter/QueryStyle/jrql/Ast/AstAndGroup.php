<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstAndGroup extends AstExpression
{
    const TYPE = 'AST::AND_GROUP';

    /** @var AstExpression[] */
    private array $expressions;

    public function __construct(array $expressions) {
        parent::__construct(self::TYPE);
        $this->expressions = $expressions;
    }

    /**
     * @return AstExpression[]
     */
    public function getExpressions(): array {
        return $this->expressions;
    }

}