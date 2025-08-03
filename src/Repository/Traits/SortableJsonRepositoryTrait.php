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
        usort($allObjects, $sorter);
        return $allObjects;
    }
}