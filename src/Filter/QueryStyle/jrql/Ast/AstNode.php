<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstNode
{
    private string $type;

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): AstNode
    {
        $this->type = $type;
        return $this;
    }


}