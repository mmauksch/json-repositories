<?php

namespace Mmauksch\JsonRepositories\Repository;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Contract\Extensions\SortableJsonRepository;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;
use Mmauksch\JsonRepositories\Contract\JsonRepository;
use Mmauksch\JsonRepositories\Repository\Traits\BasicJsonRepositoryTrait;
use Mmauksch\JsonRepositories\Repository\Traits\FilterableJsonRepositoryTrait;
use Mmauksch\JsonRepositories\Repository\Traits\SortableJsonRepositoryTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @template T of object
 * @implements  JsonRepository<T>
 * @implements SortableJsonRepository<T>
 */
abstract class AbstractJsonRepository implements JsonRepository, SortableJsonRepository
{
    use BasicJsonRepositoryTrait, FilterableJsonRepositoryTrait, SortableJsonRepositoryTrait;
    protected string $objectSubdir;
    protected string $jsonDbBase;

    public function __construct(string $jsonDbBaseDir, $objectSubdir, string $targetClass, Filesystem $filesystem, SerializerInterface $serializer)
    {
        $this->jsonDbBase = $jsonDbBaseDir;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
        $this->targetClass = $targetClass;
        $this->objectSubdir = $objectSubdir;
    }
    protected function objectStoreDirectory() : string
    {
        return Path::join($this->jsonDbBase, $this->objectSubdir);
    }

    public function findMatchingFilterObjectSorted(Filter|Closure $filter, Sorter|Closure $sorter) : iterable
    {
        $unsorted = $this->findMatchingFilter($filter);
        return $this->sortResults($unsorted, $sorter);
    }
}