<?php

namespace Mmauksch\JsonRepositories\Repository;

use Closure;
use Mmauksch\JsonRepositories\Contract\Filter;
use Mmauksch\JsonRepositories\Contract\JsonRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @template T of object
 * @implements  JsonRepository<T>
 */
abstract class AbstractJsonRepository implements JsonRepository
{
    protected string $objectSubdir;
    protected string $jsonDbBase;
    protected Filesystem $filesystem;
    protected SerializerInterface $serializer;
    protected string $targetClass;

    public function __construct(string $jsonDbBaseDir, $objectSubdir, string $targetClass, Filesystem $filesystem, SerializerInterface $serializer)
    {
        $this->jsonDbBase = $jsonDbBaseDir;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
        $this->targetClass = $targetClass;
        $this->objectSubdir = $objectSubdir;
    }

    /** @param T $object */
    public function saveObject(object $object, string $id) : object
    {
        $filename = Path::join($this->objectStoreDirectory(), "$id.json");
        $this->filesystem->dumpFile(
            $filename,
            $this->serializer->serialize($object, 'json')
        );
        return $object;
    }

    /** @return T[] */
    public function findAllObjects() : array
    {
        $result = [];
        foreach ((new Finder())->files()->name('*.json')->in($this->objectStoreDirectory()) as $objectFile) {
            $result[] = $this->serializer->deserialize($objectFile->getContents(), $this->targetClass, 'json');
        }
        return $result;
    }

    public function findObjectById(mixed $id): ?object
    {
        $path = Path::join($this->objectStoreDirectory(), "$id.json");
        if (!$this->filesystem->exists($path)) {
            return null;
        }
        return $this->serializer->deserialize(file_get_contents($path), $this->targetClass, 'json');
    }

    public function deleteObjectById(mixed $id): void
    {
        $this->filesystem->remove(Path::join($this->objectStoreDirectory(), "$id.json"));
    }

    public function findMatchingFilter(Filter|Closure $filter): iterable
    {
        $result = [];
        foreach ($this->findAllObjects() as $object) {
            if ($filter($object)) {
                $result[] = $object;
            }
        }
        return $result;
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

    protected function objectStoreDirectory() : string
    {
        return Path::join($this->jsonDbBase, $this->objectSubdir);
    }
}