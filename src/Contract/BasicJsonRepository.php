<?php

namespace Mmauksch\JsonRepositories\Contract;

/**
 * @template T of object
 */
interface BasicJsonRepository
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
}