<?php

namespace Mmauksch\JsonRepositories\Repository\Traits;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Contract\Extensions\FilterableJsonRepository;
use Mmauksch\JsonRepositories\Filter\SortableFilter;
use Symfony\Component\Finder\Finder;

/**
 * @template T of object
 * @see FilterableJsonRepository
 */
trait FilterableJsonRepositoryTrait
{
    use BasicJsonRepositoryTrait, LimitAndOffsetTrait;
    public function findMatchingFilter(Filter|Closure $filter): iterable
    {
        $result = [];
        foreach ($this->findAllObjects() as $object) {
            if ($filter($object)) {
                $result[] = $object;
            }
        }

        if ($filter instanceof SortableFilter) {
            $sorter = $filter->getSorter();
            $result = $this->sortResults($result, $sorter);
        }
        return $this->applyLimitAndOffset($filter, $result);
    }

    public function deleteMatchingFilter(Filter|Closure $filter): void
    {
        foreach ((new Finder())->files()->name('*.json')->in($this->objectStoreDirectory()) as $objectFile) {
            $object = $this->serializer->deserialize($objectFile->getContents(), $this->targetClass, 'json');
            if ($filter($object)) {
                $this->filesystem->remove($objectFile->getPathname());
            }
        }
    }
}