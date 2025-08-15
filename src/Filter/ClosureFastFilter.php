<?php

namespace Mmauksch\JsonRepositories\Filter;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\FastFilter;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;

/**
 * @template T
 * @implements Filter<T>
 */
class ClosureFastFilter implements FastFilter
{
    private array $indexes;
    private Closure $filter;

    public function __construct(Closure $filter, array $indexes) {
        $this->filter = $filter;
        $this->indexes = $indexes;
    }
    public function __invoke(object $object): bool
    {
        return ($this->filter)($object);
    }

    public function useIndexes(): array
    {
        return $this->indexes;
    }
}