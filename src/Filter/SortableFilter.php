<?php

namespace Mmauksch\JsonRepositories\Filter;

use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;

interface SortableFilter extends Filter
{
    public function __invoke(object $object): bool;

    public function getSorter(): Sorter;
}