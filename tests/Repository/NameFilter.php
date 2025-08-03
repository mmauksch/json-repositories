<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;


use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Tests\TestObjects\SimpleObject;

/**
 * @template T of SimpleObject
 * @implements Filter<SimpleObject>
 */
class NameFilter implements Filter
{
    private string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }
    public function __invoke(object $object): bool
    {
        return $object->getName() === $this->name;
    }
}