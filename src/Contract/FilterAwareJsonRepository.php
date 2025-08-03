<?php

namespace Mmauksch\JsonRepositories\Contract;

use Closure;

/**
 * @template T of object
 */
interface FilterAwareJsonRepository
{
    /**
     * @param Filter<T>|Closure $filter
     * @return iterable<T>
     */
    public function findMatchingFilter(Filter|Closure $filter) : iterable;
    public function deleteMatchingFilter(Filter|Closure $filter) : void;

}