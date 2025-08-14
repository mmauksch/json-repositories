<?php

namespace Mmauksch\JsonRepositories\Repository;


use Mmauksch\JsonRepositories\Contract\Extensions\FastFilter;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Closure;
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
    const FAST_ATTRIBUTES_DIR = "fast_attributes/";

    private array $highPerformanceAttributWishes;

    private ?array $fastAttributes = [];

    /** @var ReflectionProperty[] */
    private array $fastProperties = [];

    /**
     * @param string $jsonDbBaseDir
     * @param $objectSubdir
     * @param string $targetClass
     * @param Filesystem $filesystem
     * @param SerializerInterface $serializer
     * @param string[] $highPerformanceAttributes
     */
    public function __construct(string $jsonDbBaseDir, $objectSubdir, string $targetClass, Filesystem $filesystem, SerializerInterface $serializer, array $highPerformanceAttributes = [])
    {
        parent::__construct($jsonDbBaseDir, $objectSubdir, $targetClass, $filesystem, $serializer);
        $this->highPerformanceAttributWishes = $highPerformanceAttributes;
        $this->fastAttributes = null;
        $this->fastProperties = [];
    }

    private function fastAttributes(object $object): array
    {
        if (!is_null($this->fastAttributes)) {
            return $this->fastAttributes;
        }
        $fastAttributes = [];
        $attributes = $this->serializer->normalize($object);
        $possibleFastAttributes =  array_intersect(array_keys($attributes), $this->highPerformanceAttributWishes);
        $reflection = new ReflectionClass($this->targetClass);;
        foreach ($possibleFastAttributes as $attribute) {
            $property = null;
            $currentReflection = $reflection;
            while ($currentReflection) {
                if ($currentReflection->hasProperty($attribute)) {
                    $property = $currentReflection->getProperty($attribute);
                    $property->setAccessible(true);
                    $this->fastProperties[$attribute] = $property;
                    $fastAttributes[] = $attribute;
                    break;
                }
                $currentReflection = $currentReflection->getParentClass();
            }
        }
        $this->fastAttributes = $fastAttributes;
        return $fastAttributes;
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
        $dir = array_shift($indexDirs);
        $finder = new Finder();
        $finder->files()->depth('== 0')->in(Path::join($this->objectStoreDirectory(), self::FAST_ATTRIBUTES_DIR, $dir, $filterIndexes[$dir]));

        /** @var SplFileInfo[] $currentFiles */
        $currentFiles = [];
        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $currentFiles[$file->getFilename()] = $file;
        }

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
        $usableIndexes =  array_intersect($this->fastAttributes($filter), array_keys($filterIndexes));

        $files = $this->intersectFilesOfIndexDirectories($filterIndexes, $usableIndexes);

        $result = [];
        foreach ($files as $file) {
            $checkObject = $this->serializer->deserialize($file->getContents(), $this->targetClass, 'json');
            if ($filter($checkObject)) {
                $result[] = $checkObject;
            }
        }
        return $result;
    }

    /** @param T $object */
    public function saveObject(object $object, string $id): object
    {
        $filename = Path::join($this->objectStoreDirectory(), "$id.json");
        $this->filesystem->dumpFile(
            $filename,
            $this->serializer->serialize($object, 'json')
        );
        
        $fastAttributes = $this->fastAttributes($object);
        $fastFileNames = [];
        foreach ($fastAttributes as $attribute) {
            $dir = Path::join($this->objectStoreDirectory(), self::FAST_ATTRIBUTES_DIR, $attribute, $this->fastProperties[$attribute]->getValue($object),);
            if (!$this->filesystem->exists($dir)) {
                $this->filesystem->mkdir($dir);
            }
            $fastFileNames[] = Path::join($dir, "$id.json");
        }
        $this->deleteOldLinks($filename, $this->objectStoreDirectory());
        if (count($fastAttributes) > 0) {
            $this->filesystem->hardlink($filename, $fastFileNames);
        }

        return $object;
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

}