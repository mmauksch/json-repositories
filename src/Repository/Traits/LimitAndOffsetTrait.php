<?php

namespace Mmauksch\JsonRepositories\Repository\Traits;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Contract\Extensions\Limit;
use Mmauksch\JsonRepositories\Contract\Extensions\Offset;

trait LimitAndOffsetTrait
{
    /**
     * @param Filter|Closure $filter
     * @param array $sorted
     * @return array
     */
    public function applyLimitAndOffset(Filter|Closure $filter, array $sorted): array
    {
        $offset = $filter instanceof Offset ? $filter->getOffset() : 0;
        if (!$filter instanceof Limit) {
            return $sorted;
        }
        $reduced = array_slice($sorted, $offset, $filter->getLimit());
        return $reduced;
    }

}