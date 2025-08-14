<?php

namespace Mmauksch\JsonRepositories\Contract\Extensions;

/**
 * @template T of object
 */
interface FastFilter extends Filter
{
    /**
     * @return string[]
     */
    public function useIndexes() : array;

}