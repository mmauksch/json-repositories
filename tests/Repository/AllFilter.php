<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;


use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Tests\TestObjects\SimpleObject;

/**
 * @template T of SimpleObject
 * @implements Filter<SimpleObject>
 */
class AllFilter implements Filter
{

    public function __construct() {
    }
    public function __invoke(object $object): bool
    {
        return true;
    }
}