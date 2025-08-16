<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

use Mmauksch\JsonRepositories\Filter\QueryStyle\QueryBuilder;

class WhereBuilder
{
    private QueryBuilder $parent;
    private ConditionGroup $current;

    public function __construct(QueryBuilder $parent, ConditionGroup $current)
    {
        $this->parent = $parent;
        $this->current = $current;
    }

    public function condition(string $attribute, Operation|String $operation, mixed $value): self
    {
        $op = $operation instanceof Operation? $operation: Operation::fromString($operation);
        if ($op == Operation::EQ && ! $value instanceof RefAttribute){
            $this->parent->addEqualIndexValue($attribute, $value);
        }
        $this->current->add(new Condition($attribute,$op,$value));
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
        $this->current->add($group);
        return new GroupBuilder($this->parent, $this, $group);
    }

    public function end(): QueryBuilder
    {
        return $this->parent;
    }
}