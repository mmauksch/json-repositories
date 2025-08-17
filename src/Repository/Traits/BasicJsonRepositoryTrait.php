<?php

namespace Mmauksch\JsonRepositories\Repository\Traits;

use Mmauksch\JsonRepositories\Contract\BasicJsonRepository;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @template T of object
 * @see BasicJsonRepository
 */
trait BasicJsonRepositoryTrait
{
    protected Filesystem $filesystem;
    protected string $targetClass;
    protected SerializerInterface $serializer;

    /** @param T $object */
    public function saveObject(object $object, string $id): object
    {
        $filename = Path::join($this->objectStoreDirectory(), "$id.json");
        $this->filesystem->dumpFile(
            $filename,
            $this->serializer->serialize($object, 'json')
        );
        return $object;
    }

    public function getTargetClass(): string
    {
        return $this->targetClass;
    }


    protected function findAllObjectFiles(): Finder
    {
        return (new Finder())->files()->depth('== 0')->name('*.json')->in($this->objectStoreDirectory());
    }

    /** @return T */
    protected function deserializeFileObject(SplFileInfo $objectFile): mixed
    {
        return $this->serializer->deserialize($objectFile->getContents(), $this->targetClass, 'json');
    }

    /** @return T[] */
    public function findAllObjects(): array
    {
        $result = [];
        foreach ($this->findAllObjectFiles() as $objectFile) {
            $result[] = $this->deserializeFileObject($objectFile);
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

    protected abstract function objectStoreDirectory(): string;
}