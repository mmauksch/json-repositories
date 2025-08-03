<?php

namespace Mmauksch\JsonRepositories\Contract\Extensions;

use Closure;

/**
 * @template T of object
 */
interface SortableJsonRepository
{
    /**
     * @param Sorter|Closure(T, T) : int $sorter
     * @return iterable<T>
     */
    public function findAllObjectSorted(Sorter|Closure $sorter) : iterable;
}