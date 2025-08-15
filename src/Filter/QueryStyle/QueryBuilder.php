<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle;

use Mmauksch\JsonRepositories\Contract\Extensions\FastFilter;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\Condition;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\ConditionGroup;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\ConditionGroupType;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\QuerySorter;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\SortingStep;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\SortOrder;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\WhereBuilder;
use Mmauksch\JsonRepositories\Filter\SortableFilter;

class QueryBuilder implements FastFilter, SortableFilter
{
    /** @var string[][] */
    private array $equalIndexesValues;

//    private array $select = [];
//    private string $from;
    private ConditionGroup $root;

    private Sorter $sorter;
    /** @var SortingStep[] */
    private array $sorting = [];

    public function __construct()
    {
//        $this->from = $from;
        $this->equalIndexesValues = [];
        $this->sorter = new QuerySorter();
        $this->sorting = [];
    }
//
//    public function select(array $fields): self
//    {
//        $this->select = $fields;
//        return $this;
//    }
//
//    public function from(string $table): self
//    {
//        $this->from = $table;
//        return $this;
//    }

    public function where(ConditionGroupType $initialConditionGroupType = ConditionGroupType::AND): WhereBuilder
    {
        $this->root = new ConditionGroup($initialConditionGroupType);
        return new WhereBuilder($this, $this->root);
    }

    public function orderBy(string $attribute, SortOrder|string $direction = "asc"): self {
        $orderDirection = $direction instanceof SortOrder? $direction : SortOrder::fromString($direction);
        $this->sorting[] = new SortingStep($attribute, $orderDirection);
        return $this;
    }

    public function addEqualIndexValue(string $attribute, mixed $value): self
    {
        if (!isset($this->equalIndexesValues[$attribute])) {
            $this->equalIndexesValues[$attribute] = [];
        }
        $this->equalIndexesValues[$attribute][] = $value;
        return $this;
    }
//    public function getStructure(): array
//    {
//        return [
////            "select" => $this->select,
////            "from"   => $this->from,
//            "where"  => $this->serializeGroup($this->root),
//        ];
//    }
//
//    private function serializeGroup(ConditionGroup $group): array
//    {
//        return [
//            "type" => $group->type,
//            "conditions" => array_map(function ($c) {
//                if ($c instanceof Condition) {
//                    return [
//                        "attribute" => $c->attribute,
//                        "operator"  => $c->operation->value,
//                        "value"     => $c->value,
//                    ];
//                }
//                return $this->serializeGroup($c);
//            }, $group->conditions)
//        ];
//    }

    public function __invoke(object $object): bool
    {
        return $this->root->evaluate($object);
    }

    public function useIndexes(): array
    {
        return $this->equalIndexesValues;
    }

    public function getSorter(): Sorter
    {
        $this->sorter->changeSorting($this->sorting);
        return $this->sorter;
    }

}