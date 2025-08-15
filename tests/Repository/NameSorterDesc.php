<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;
use Mmauksch\JsonRepositories\Tests\TestObjects\SimpleObject;

/**
 * @implements Sorter<SimpleObject>
 */
class NameSorterDesc implements Sorter
{

    public function __invoke(object $lhs, object $rhs): int
    {
        return strcmp($rhs->getName(), $lhs->getName());
    }
}