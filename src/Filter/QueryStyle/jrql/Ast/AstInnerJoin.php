<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstInnerJoin extends AstNode
{
    const TYPE = 'AST::INNER_JOIN';

    private string $repository;
    private string $left_field;
    private string $right_field;

    public function __construct(string $repository, string $left_field, string $right_field) {
        parent::__construct(self::TYPE);
        $this->repository = $repository;
        $this->left_field = $left_field;
        $this->right_field = $right_field;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }
    public function getLeftField(): string
    {
        return $this->left_field;
    }
    public function getRightField(): string
    {
        return $this->right_field;
    }

}