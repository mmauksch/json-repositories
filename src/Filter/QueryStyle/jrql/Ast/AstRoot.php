<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

class AstRoot extends AstNode
{
    const TYPE = 'AST::ROOT';

    private ?AstWhere $where = null;
    private ?AstLimit $limit = null;
    private ?AstOffset $astOffset = null;

    private ?AstOrderBy $orderBy = null;

    /*** @var AstInnerJoin[] */
    private array $innerJoins = [];


    public function __construct()
    {
        parent::__construct(self::TYPE);
    }

    public function getWhere(): ?AstWhere
    {
        return $this->where;
    }

    public function setWhere(?AstWhere $where): AstRoot
    {
        if ($this->where !== null) {
            throw new \Exception('Where clause can only be set once');
        }
        $this->where = $where;
        return $this;
    }

    public function getLimit(): ?AstLimit
    {
        return $this->limit;
    }

    public function setLimit(?AstLimit $limit): AstRoot
    {
        if ($this->limit !== null) {
            throw new \Exception('Limit clause can only be set once');
        }
        $this->limit = $limit;
        return $this;
    }

    public function getOffset(): ?AstOffset
    {
        return $this->astOffset;
    }

    public function setOffset(?AstOffset $astOffset): AstRoot
    {
        if ($this->astOffset !== null) {
            throw new \Exception('Offset clause can only be set once');
        }
        $this->astOffset = $astOffset;
        return $this;
    }

    public function getOrderBy(): ?AstOrderBy
    {
        return $this->orderBy;
    }

    public function setOrderBy(?AstOrderBy $orderBy): AstRoot
    {
        if ($this->orderBy !== null) {
            throw new \Exception('OrderBy clause can only be set once');
        }
        $this->orderBy = $orderBy;
        return $this;
    }

    /***
     * @return AstInnerJoin[]
     */
    public function getInnerJoins(): array
    {
        return $this->innerJoins;
    }

    /***
     * @param AstInnerJoin[] $innerJoins
     * @return $this
     */
    public function setInnerJoins(array $innerJoins): AstRoot
    {
        $this->innerJoins = $innerJoins;
        return $this;
    }


    public function addInnerJoin(AstInnerJoin $innerJoin): AstRoot
    {
        $this->innerJoins[] = $innerJoin;
        return $this;
    }

}