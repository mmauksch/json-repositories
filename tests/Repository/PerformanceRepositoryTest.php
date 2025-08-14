<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;
use Mmauksch\JsonRepositories\Repository\GenericJsonRepository;
use Mmauksch\JsonRepositories\Repository\HighPerformanceJsonRepository;
use Mmauksch\JsonRepositories\Tests\TestConstants;
use Mmauksch\JsonRepositories\Tests\TestObjects\ComplexObject;
use Mmauksch\JsonRepositories\Tests\TestObjects\SimpleObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class PerformanceRepositoryTest extends TestCase
{
    protected static string $repodir = 'complex';
    protected static ?string $temppath = null;
    protected static Filesystem $filesystem;
    private HighPerformanceJsonRepository $highPerformanceRepository;

    public static function setUpBeforeClass(): void
    {
        self::$filesystem = new Filesystem();
        self::$temppath = sys_get_temp_dir().'/'.'jsondb_'.uniqid();
        self::$filesystem->mkdir(self::$temppath);
    }

    public static function tearDownAfterClass(): void
    {
        if(is_dir(self::$temppath))
            self::$filesystem->remove(self::$temppath);
        self::$temppath = null;
    }

    public function ComplexObjectFirst(): ComplexObject
    {
        return (new ComplexObject())
            ->setId('first-id-' . uniqid())
            ->setName('aa-first-name')
            ->setActive(true)
            ->setDescription('aa-first-description')
            ->setAge(10)
            ->setCompany("company");
    }
    public function ComplexObjectSecond(): ComplexObject
    {
        return (new ComplexObject())
            ->setId('second-id-' . uniqid())
            ->setName('bb-second-name')
            ->setActive(false)
            ->setDescription('bb-second-description')
            ->setAge(20)
            ->setCompany("company");
    }


    protected function setUp(): void
    {
        $repositoryDir = Path::join(self::$temppath, static::$repodir);
        if(is_dir($repositoryDir))
            self::$filesystem->remove($repositoryDir);
        self::$filesystem->mkdir($repositoryDir);
        $this->highPerformanceRepository = new HighPerformanceJsonRepository(
            self::$temppath,
            self::$repodir,
            ComplexObject::class,
            self::$filesystem,
            TestConstants::JsonSerializer(),
            ['name', 'company']
        );

    }

    public function testCanStoreAndRetrieveSimpleObjectHP()
    {
        $testObject = $this->ComplexObjectFirst();
        $id = $testObject->getId();
        $this->assertFileDoesNotExist(Path::join(self::$temppath, static::$repodir, "$id.json"));
        $this->highPerformanceRepository->saveObject($testObject, $id);
        $this->assertTrue(self::$filesystem->exists(Path::join(self::$temppath, static::$repodir, "$id.json")));
        $loaded = $this->highPerformanceRepository->findObjectById($id);
        $this->assertNotSame($testObject, $loaded);
        $this->assertEquals($testObject, $loaded);
    }

    public function testWillReturnNullIfObjectNotFound()
    {
        $this->assertNull($this->highPerformanceRepository->findObjectById('not-existing-id'));
    }

    public function testCanStoreMultipleObjects()
    {
        $testObjectFirst = $this->ComplexObjectFirst();
        $testObjectSecond = $this->ComplexObjectSecond();
        $this->highPerformanceRepository->saveObject($testObjectFirst, $testObjectFirst->getId());
        $this->highPerformanceRepository->saveObject($testObjectSecond, $testObjectSecond->getId());
        $this->assertFileExists(Path::join(self::$temppath, static::$repodir, "{$testObjectFirst->getId()}.json"));
        $this->assertFileExists(Path::join(self::$temppath, static::$repodir, "{$testObjectSecond->getId()}.json"));
        $all = $this->highPerformanceRepository->findAllObjects();
        $this->assertCount(2, $all);
    }

    public function testCanDeleteObject()
    {
        $testObject = $this->ComplexObjectFirst();
        $id = $testObject->getId();
        $this->highPerformanceRepository->saveObject($testObject, $id);
        $this->assertTrue(self::$filesystem->exists(Path::join(self::$temppath, static::$repodir, "$id.json")));
        $this->highPerformanceRepository->deleteObjectById($id);
        $this->assertFalse(self::$filesystem->exists(Path::join(self::$temppath, static::$repodir, "$id.json")));
    }

    public function testDeleteWillNotFailIfObjectDoesNotExist()
    {
        $this->assertFileDoesNotExist(Path::join(self::$temppath, static::$repodir, "not-existing-id.json"));
        $this->highPerformanceRepository->deleteObjectById('not-existing-id');
        $this->assertTrue(true);
    }

    public static function FilterProvider() : array {
        return [
            'complexFilter' => [
                new ComplexFilter('company'),
                2
            ],
            'complexDoubleFilter' => [
                new ComplexDoubleFilter('aa-first-name', 'company'),
                1
            ],
            'closure' => [
                function (SimpleObject $object) {
                    return $object->getName() === 'aa-first-name';
                },
                1
            ]
        ];
    }

    /**
     * @dataProvider FilterProvider
     * @param Filter|Closure $filter
     * @return void
     */
    public function testCanFindWithFilter(Filter|Closure $filter, int $expectedCount)
    {
        $first = $this->ComplexObjectFirst();
        $second = $this->ComplexObjectSecond();
        $this->highPerformanceRepository->saveObject($first, $first->getId());
        $this->highPerformanceRepository->saveObject($second, $second->getId());
        $this->assertCount(2, $this->highPerformanceRepository->findAllObjects());
        $this->assertCount($expectedCount, $this->highPerformanceRepository->findMatchingFilter($filter));
    }


    public function testCanFindWithFilterObjectNotFound()
    {
        $first = $this->ComplexObjectFirst();
        $second = $this->ComplexObjectSecond();
        $this->highPerformanceRepository->saveObject($first, $first->getId());
        $this->highPerformanceRepository->saveObject($second, $second->getId());
        $this->assertCount(2, $this->highPerformanceRepository->findAllObjects());
        $this->assertCount(0, $this->highPerformanceRepository->findMatchingFilter(new NameFilter('not-existing-name')));
    }

    public function testCanFindWithFilterAsClosure()
    {
        $first = $this->ComplexObjectFirst();
        $second = $this->ComplexObjectSecond();
        $this->highPerformanceRepository->saveObject($first, $first->getId());
        $this->highPerformanceRepository->saveObject($second, $second->getId());
        $this->assertCount(2, $this->highPerformanceRepository->findAllObjects());
        $resultMatching = $this->highPerformanceRepository->findMatchingFilter(
            function (ComplexObject $object) use ($first) {
                return $object->getId() === $first->getId();
            });
        $this->assertCount(1, $resultMatching);
        $this->assertEquals($first, $resultMatching[0]);

    }

    public static function AscendingSorter() : array
    {
        return [
            'sorterObject' => [
                new NameSorter()
            ],
            'closure' => [
                function (SimpleObject $a, SimpleObject $b) {
                    return strcmp($a->getName(), $b->getName());
                }
            ]
        ];
    }

    /**
     * @dataProvider AscendingSorter
     * @param Sorter|Closure $sorter
     * @return void
     */
    public function testCanRetrieveSorted(Sorter|Closure $sorter)
    {
        $first = $this->ComplexObjectFirst();
        $second = $this->ComplexObjectSecond();
        $this->highPerformanceRepository->saveObject($first, $first->getId());
        $this->highPerformanceRepository->saveObject($second, $second->getId());
        $result = $this->highPerformanceRepository->findAllObjectSorted($sorter);
        $this->assertCount(2, $result);
        $this->assertEquals($first, $result[0]);
        $this->assertEquals($second, $result[1]);
    }
}