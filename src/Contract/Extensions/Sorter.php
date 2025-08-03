<?php

namespace Mmauksch\JsonRepositories\Contract\Extensions;

/**
 * @template T of object
 */
interface Sorter
{
    /**
     * @param T $lhs
     * @param T $rhs
     * @return int
     */
    public function __invoke(object $lhs, object $rhs) : int;
}