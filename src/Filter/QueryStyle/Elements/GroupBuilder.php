<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

use Mmauksch\JsonRepositories\Filter\QueryStyle\QueryBuilder;

class GroupBuilder
{
    private QueryBuilder $queryBuilder;
    private WhereBuilder|GroupBuilder $upper;
    private ConditionGroup $group;

    public function __construct(QueryBuilder $queryBuilder, WhereBuilder|GroupBuilder $upper, ConditionGroup $group)
    {
        $this->queryBuilder = $queryBuilder;
        $this->upper = $upper;
        $this->group = $group;
    }

    public function condition(string $attribute, Operation|String $operation, mixed $value): self
    {
        $op = $operation instanceof Operation? $operation: Operation::fromString($operation);
        if ($op == Operation::EQ){
            $this->queryBuilder->addEqualIndexValue($attribute, $value);
        }
        $this->group->add(new Condition($attribute,$op,$value));
        return $this;
    }

    public function andX(): GroupBuilder
    {
        return $this->addGroup(ConditionGroupType::AND);
    }

    public function orX(): GroupBuilder
    {
        return $this->addGroup(ConditionGroupType::OR);
    }

    private function addGroup(ConditionGroupType $type): GroupBuilder
    {
        $group = new ConditionGroup($type);
        $this->group->add($group);
        return new GroupBuilder($this->queryBuilder, $this, $group);
    }

    public function endX(): WhereBuilder|GroupBuilder
    {
        return $this->upper;
    }
}