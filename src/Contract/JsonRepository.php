<?php

namespace Mmauksch\JsonRepositories\Contract;

use Closure;

/**
 * @template T of object
 */
interface JsonRepository
{
    /**
     * @param mixed $id
     * @return  T
     */
    public function findObjectById(string $id) : ?object;

    /**
     * @return iterable<T>
     */
    public function findAllObjects() : iterable;

    /**
     * @param T $object
     * @return void
     */
    public function saveObject(object $object, string $id) : object;

    public function deleteObjectById(mixed $id) : void;

    /**
     * @param Filter<T>|Closure $filter
     * @return iterable<T>
     */
    public function findMatchingFilter(Filter|Closure $filter) : iterable;
}