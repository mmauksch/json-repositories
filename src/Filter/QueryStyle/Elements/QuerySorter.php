<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;

class QuerySorter implements Sorter
{
    use GetPropertyValueTrait;

    /** @var SortingStep[] */
    private array $sorting = [];

    public function changeSorting(array $sorting): void {
        $this->sorting = $sorting;
    }

    public function __invoke(object $lhs, object $rhs): int
    {
        foreach ($this->sorting as $sortStep) {
            $lhsValue = $this->getValue($lhs, $sortStep->getAttribute());
            $rhsValue = $this->getValue($rhs, $sortStep->getAttribute());
            $cmp = $lhsValue <=> $rhsValue;
            if ($cmp !== 0) {
                return $sortStep->getOrder() === SortOrder::DESC ? -$cmp : $cmp;
            }
        }
        return 0;
    }
}