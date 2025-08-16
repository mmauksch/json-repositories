<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle;

use Mmauksch\JsonRepositories\Contract\Extensions\FastFilter;
use Mmauksch\JsonRepositories\Contract\Extensions\Limit;
use Mmauksch\JsonRepositories\Contract\Extensions\Offset;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\Condition;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\ConditionGroup;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\ConditionGroupType;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\GetPropertyValueTrait;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\InnerJoinBuilder;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\QuerySorter;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\SortingStep;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\SortOrder;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\WhereBuilder;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstLimit;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOffset;
use Mmauksch\JsonRepositories\Filter\SortableFilter;
use Mmauksch\JsonRepositories\Repository\AbstractJsonRepository;

class QueryBuilder implements FastFilter, SortableFilter, Limit, Offset
{
    use GetPropertyValueTrait;
    /** @var array<string, array<string, mixed>|string> */
    private array $equalIndexesValues;

//    private array $select = [];
//    private string $from;
    private ConditionGroup $root;

    /*** @var InnerJoinBuilder[] */
    private $joins;
    private Sorter $sorter;
    /** @var SortingStep[] */
    private array $sorting = [];
    private ?int $limit;
    private int $offset;

    public function __construct()
    {
//        $this->from = $from;
        $this->equalIndexesValues = [];
        $this->sorter = new QuerySorter();
        $this->sorting = [];
        $this->root = new ConditionGroup(ConditionGroupType::AND);
        $this->limit = null;
        $this->offset = 0;
        $this->joins = [];
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

    public function innerJoin(AbstractJsonRepository $repository, $name_prefix): InnerJoinBuilder
    {
        $this->joins[$name_prefix] = new InnerJoinBuilder($this, $repository, $name_prefix);
        return $this->joins[$name_prefix];
    }

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

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @internal This method should only be used by the QueryBuilder itself
     * @param string $attribute
     * @param mixed $value
     * @return $this
     */
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

//    public function __invoke(object $object): bool
//    {
//        if (count($this->root->conditions) == 0) {
//            return true;
//        }
//
//        if (count($this->joins) === 0) {
//            return $this->root->evaluate($object, []);
//        }
//
//
//        $joinResults = [];
//        foreach ($this->joins as $prefix => $joinBuilder) {
//            if ($joinBuilder->__getType() === $joinBuilder::TYPE_INTERNAL) {
//                $objectJoinValue = $this->getValue($object, $joinBuilder->__getNoRefAttribute());
//                $queryBuilder = (new QueryBuilder())
//                    ->where()
//                    ->condition($joinBuilder->__getJoinRepoAttribute(), '=', $objectJoinValue)
//                    ->end();
//                $joinResponse = $joinBuilder->__getRepository()->findMatchingFilter($queryBuilder);
//                $joinResults[$prefix] = $joinResponse;
//            }
//        }
//
//        foreach ($this->cartesianProduct($joinResults) as $combination) {
//            if ($this->root->evaluate($object, $combination)) {
//                return true;
//            }
//        }
//
//        return false;
//    }
//
//    private function cartesianProduct(array $arrays): array
//    {
//        $result = [[]];
//        foreach ($arrays as $prefix => $values) {
//            $append = [];
//            foreach ($result as $product) {
//                foreach ($values as $v) {
//                    $product[$prefix] = [$v];
//                    $append[] = $product;
//                }
//            }
//            $result = $append;
//        }
//        return $result;
//    }





    public function __invoke(object $object): bool
    {
        if (count($this->root->conditions) === 0) {
            return true;
        }

        if (count($this->joins) === 0) {
            return $this->root->evaluate($object, []);
        }

        // 1. alle direkten (TYPE_INTERNAL) Joins auflösen
        $directJoinResults = [];
        foreach ($this->joins as $prefix => $joinBuilder) {
            if ($joinBuilder->__getType() === InnerJoinBuilder::TYPE_INTERNAL) {
                $directJoinResults[$prefix] = $this->resolveDirectJoin($object, $joinBuilder, $prefix);
            }
        }

        // 2. baue kartesisches Produkt aller direkten Joins
        foreach ($this->cartesianProduct($directJoinResults) as $combination) {
            // 3. rekursiv verkettete (TYPE_EXTERNAL) Joins anhängen
            $fullCombination = $this->resolveNestedJoins($combination);

            if ($this->root->evaluate($object, $fullCombination)) {
                return true;
            }
        }

        return false;
    }

    private function resolveDirectJoin(object $object, InnerJoinBuilder $joinBuilder, string $prefix): array
    {
        $objectJoinValue = $this->getValue($object, $joinBuilder->__getNoRefAttribute()->getAttribute());

        $queryBuilder = (new QueryBuilder())
            ->where()
            ->condition($joinBuilder->__getJoinRepoAttribute()->getAttribute(), '=', $objectJoinValue)
            ->end();

        $matches = $joinBuilder->__getRepository()->findMatchingFilter($queryBuilder);

        return array_map(fn($m) => [$prefix => $m], $matches);
    }


    private function resolveNestedJoins(array $combination): array
    {
        $expanded = [$combination];

        foreach ($this->joins as $prefix => $joinBuilder) {
            if ($joinBuilder->__getType() === InnerJoinBuilder::TYPE_EXTERNAL) {
                $parentPrefix = $joinBuilder->__getOtherRefAttribute()->getRef();
                $parentAttr   = $joinBuilder->__getOtherRefAttribute()->getAttribute();

                $newExpanded = [];

                foreach ($expanded as $combo) {
                    if (!isset($combo[$parentPrefix])) {
                        // Parent fehlt in dieser Kombination → ignoriere
                        $newExpanded[] = $combo;
                        continue;
                    }

                    $parentObject = $combo[$parentPrefix];
                    $joinValue = $this->getValue($parentObject, $parentAttr);

                    $queryBuilder = (new QueryBuilder())
                        ->where()
                        ->condition($joinBuilder->__getJoinRepoAttribute()->getAttribute(), '=', $joinValue)
                        ->end();

                    $childResults = $joinBuilder->__getRepository()->findMatchingFilter($queryBuilder);

                    if (empty($childResults)) {
                        continue; // keine Treffer
                    }

                    foreach ($childResults as $child) {
                        $newCombo = $combo;
                        $newCombo[$prefix] = $child;
                        $newExpanded[] = $newCombo;
                    }
                }

                $expanded = $newExpanded;
            }
        }

        return $expanded[0] ?? $combination;
    }



    private function cartesianProduct(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $values) {
            $append = [];
            foreach ($result as $product) {
                foreach ($values as $combination) {
                    $append[] = array_merge($product, $combination);
                }
            }
            $result = $append;
        }
        return $result;
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

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}