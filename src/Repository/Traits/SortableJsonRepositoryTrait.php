<?php

namespace Mmauksch\JsonRepositories\Repository\Traits;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\SortableJsonRepository;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;

/**
 * @template T of object
 * @see SortableJsonRepository
 */
trait SortableJsonRepositoryTrait
{
    use BasicJsonRepositoryTrait;
    public function findAllObjectSorted(Sorter|Closure $sorter) : iterable
    {
        $allObjects = $this->findAllObjects();
        return $this->sortResults($allObjects, $sorter);
    }

    protected function sortResults(array $unsorted, Sorter|Closure $sorter) : array
    {
        usort($unsorted, $sorter);
        return $unsorted;
    }

}