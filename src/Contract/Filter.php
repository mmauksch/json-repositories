<?php

namespace Mmauksch\JsonRepositories\Contract;

/**
 * @template T of object
 */
interface Filter
{
    /**
     * @param T $object
     * @return bool
     */
    public function __invoke(object $object) : bool;
}