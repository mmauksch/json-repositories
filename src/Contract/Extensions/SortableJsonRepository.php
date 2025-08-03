<?php

namespace Mmauksch\JsonRepositories\Contract\Extensions;

use Closure;

interface SortableJsonRepository
{
    public function findAllObjectSorted(Sorter|Closure $sorter);
}