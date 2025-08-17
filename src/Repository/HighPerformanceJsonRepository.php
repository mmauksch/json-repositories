<?php

namespace Mmauksch\JsonRepositories\Repository;


use Mmauksch\JsonRepositories\Contract\Extensions\FastFilter;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Closure;
use Mmauksch\JsonRepositories\Filter\SortableFilter;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @template T
 * @extends AbstractJsonRepository<T>
 */
class HighPerformanceJsonRepository extends GenericJsonRepository
{
    const INDEXES_DIR = "indexes/";

    private array $possibleIndexes;

    private ?array $indexAttributes = [];

    /** @var ReflectionProperty[] */
    private array $indexProperties = [];

    /**
     * @param string $jsonDbBaseDir
     * @param $objectSubdir
     * @param string $targetClass
     * @param Filesystem $filesystem
     * @param SerializerInterface $serializer
     * @param string[] $possibleIndexAttributes
     */
    public function __construct(string $jsonDbBaseDir, $objectSubdir, string $targetClass, Filesystem $filesystem, SerializerInterface $serializer, array $possibleIndexAttributes = [])
    {
        parent::__construct($jsonDbBaseDir, $objectSubdir, $targetClass, $filesystem, $serializer);
        $this->possibleIndexes = $possibleIndexAttributes;
        $this->indexAttributes = null;
        $this->indexProperties = [];
    }

    private function buildIndexAttributes(object $object): array
    {
        if (!is_null($this->indexAttributes)) {
            return $this->indexAttributes;
        }
        $fastAttributes = [];
        $attributes = $this->serializer->normalize($object);
        $possibleFastAttributes =  array_intersect(array_keys($attributes), $this->possibleIndexes);
        $reflection = new ReflectionClass($this->targetClass);;
        foreach ($possibleFastAttributes as $attribute) {
            $property = null;
            $currentReflection = $reflection;
            while ($currentReflection) {
                if ($currentReflection->hasProperty($attribute)) {
                    $property = $currentReflection->getProperty($attribute);
                    $this->indexProperties[$attribute] = $property;
                    $fastAttributes[] = $attribute;
                    break;
                }
                $currentReflection = $currentReflection->getParentClass();
            }
        }
        $this->indexAttributes = $fastAttributes;
        return $fastAttributes;
    }



    /**
     * @param string $index
     * @param string[] $indexValues
     * @return SplFileInfo[]
     */
    private function mergeMultipleFilterIndexes(string $index, array $indexValues): array
    {
        /** @var SplFileInfo[] $currentFiles */
        $currentFiles = [];

        foreach ($indexValues as $indexDir) {
            $dirPath = Path::join($this->objectStoreDirectory(), self::INDEXES_DIR, $index, $indexDir);
            if(!$this->filesystem->exists($dirPath))
            {
                continue;
            }

            $finder = new Finder();
            $finder->files()->depth('== 0')->in($dirPath);
            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $currentFiles[$file->getFilename()] = $file;
            }
        }
        return $currentFiles;
    }


    /**
     * @param string[] $indexDirs
     * @param SplFileInfo[]|null $currentFilesIntersection
     * @return SplFileInfo[]
     */
    private function intersectFilesOfIndexDirectories(array $filterIndexes, array $indexDirs, ?array $currentFilesIntersection = null): array
    {
        if (empty($indexDirs)) {
            return $currentFilesIntersection ?? [];
        }

        $index = array_shift($indexDirs);
        $indexValues = $filterIndexes[$index];

        if (is_string($indexValues)) {
            $indexValues = [$indexValues];
        }
        $currentFiles = $this->mergeMultipleFilterIndexes($index, $indexValues);

        if ($currentFilesIntersection === null) {
            $newFilesIntersection = $currentFiles;
        } else {
            $newFilesIntersection = [];
            foreach ($currentFilesIntersection as $name => $fileInfo) {
                if (isset($currentFiles[$name])) {
                    $newFilesIntersection[$name] = $currentFiles[$name];
                }
            }
        }
        return $this->intersectFilesOfIndexDirectories($filterIndexes, $indexDirs, $newFilesIntersection);
    }

    public function findMatchingFilter(Filter|Closure $filter): iterable
    {
        if (!$filter instanceof FastFilter) {
            return parent::findMatchingFilter($filter);
        }

        $filterIndexes = $filter->useIndexes();
        $usableIndexes =  array_intersect($this->buildIndexAttributes($filter), array_keys($filterIndexes));

        $result = [];
        if (empty($usableIndexes)) {
            return parent::findMatchingFilter($filter);
        }else{
            $files = $this->intersectFilesOfIndexDirectories($filterIndexes, $usableIndexes);
            foreach ($files as $file) {
                $checkObject = $this->serializer->deserialize($file->getContents(), $this->targetClass, 'json');
                if ($filter($checkObject)) {
                    $result[] = $checkObject;
                }
            }
        }

        if ($filter instanceof SortableFilter) {
            $sorter = $filter->getSorter();
            $result = $this->sortResults($result, $sorter);
        }

        return $this->applyLimitAndOffset($filter, $result);
    }

    private function filenameForId(mixed $id): string
    {
        return Path::join($this->objectStoreDirectory(), "$id.json");
    }

    /** @param T $object */
    public function saveObject(object $object, string $id): object
    {
        $filename = $this->filenameForId($id);
        $this->filesystem->dumpFile(
            $filename,
            $this->serializer->serialize($object, 'json')
        );
        $this->builtIndexForObjectAndPrimaryFile($object, $filename);

        return $object;
    }

    private function builtIndexForObjectAndPrimaryFile(object $object, string $primaryFilename): void
    {
        $basename = basename($primaryFilename);
        $indexAttributes = $this->buildIndexAttributes($object);
        $indexFileNames = [];
        foreach ($indexAttributes as $attribute) {
            $dir = Path::join($this->objectStoreDirectory(), self::INDEXES_DIR, $attribute, $this->indexProperties[$attribute]->getValue($object),);
            if (!$this->filesystem->exists($dir)) {
                $this->filesystem->mkdir($dir);
            }
            $indexFileNames[] = Path::join($dir, $basename);
        }
        $this->deleteOldLinks($primaryFilename, $this->objectStoreDirectory());
        if (count($indexAttributes) > 0) {
            $this->filesystem->hardlink($primaryFilename, $indexFileNames);
        }
    }

    public function deleteObjectById(mixed $id): void
    {
        $filename = Path::join($this->objectStoreDirectory(), "$id.json");
        $this->deleteOldLinks($filename, $this->objectStoreDirectory());
        $this->filesystem->remove(Path::join($this->objectStoreDirectory(), "$id.json"));
    }

    private function deleteOldLinks(string $filename, string $topdir): void {
        $basename = basename($filename);
        foreach ((new Finder())->directories()->in($topdir) as $indexDir) {
            $tryFilename = Path::join($indexDir->getPathname(), $basename);
            if ($this->filesystem->exists($tryFilename)) {
                $this->filesystem->remove($tryFilename);
            }
        }
    }


    public function reindex(): void{
        $this->filesystem->remove(Path::join($this->objectStoreDirectory(), self::INDEXES_DIR));
        $this->filesystem->mkdir(Path::join($this->objectStoreDirectory(), self::INDEXES_DIR));
        $all = $this->findAllObjectFiles();
        foreach ($all as $objectFile) {
            $this->builtIndexForObjectAndPrimaryFile($this->deserializeFileObject($objectFile), $objectFile->getPathname());
        }
    }


    public function deleteMatchingFilter(Filter|Closure $filter) : void{
        throw new \Exception("Not implemented");
    }



}