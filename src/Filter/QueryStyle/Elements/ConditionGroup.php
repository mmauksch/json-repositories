<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

class ConditionGroup
{
    public ConditionGroupType $type;

    /** @var array<Condition|ConditionGroup> */
    public array $conditions = [];

    /***
     * @param ConditionGroupType $type
     * @param array<Condition|ConditionGroup> $conditions
     */
    public function __construct(
        ConditionGroupType $type,
        array $conditions = []
    ) {
        $this->type = $type;
        $this->conditions = $conditions;
    }

    public function add(Condition|ConditionGroup $conditionGroup): void
    {
        $this->conditions[] = $conditionGroup;
    }

    /**
     * @param object $data
     * @param array<string, array|iterable> $joinSet
     * @return bool
     */
    public function evaluate(object $data, array $joinSet = []): bool
    {
        if ($this->type === ConditionGroupType::AND) {
            foreach ($this->conditions as $c) {
                if (!$c->evaluate($data, $joinSet)) {
                    return false;
                }
            }
            return true;
        }

        if ($this->type === ConditionGroupType::OR) {
            foreach ($this->conditions as $c) {
                if ($c->evaluate($data, $joinSet)) {
                    return true;
                }
            }
            return false;
        }

        throw new \LogicException("Unknown group type: " . $this->type->value);
    }

}