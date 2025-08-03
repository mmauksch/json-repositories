<?php

namespace Mmauksch\JsonRepositories\Repository\Traits;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;

trait SortableJsonRepositoryTrait
{
    use BasicJsonRepositoryTrait;
    public function findAllObjectSorted(Sorter|Closure $sorter)
    {
        $allObjects = $this->findAllObjects();
        usort($allObjects, $sorter);
        return $allObjects;
    }
}