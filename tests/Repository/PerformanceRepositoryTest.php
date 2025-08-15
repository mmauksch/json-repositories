<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;
use Mmauksch\JsonRepositories\Filter\ClosureFastFilter;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\Operation;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\SortOrder;
use Mmauksch\JsonRepositories\Filter\QueryStyle\QueryBuilder;
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

    public static function ComplexObjectFirst(): ComplexObject
    {
        return (new ComplexObject())
            ->setId('aa-first-id-' . uniqid())
            ->setName('aa-first-name')
            ->setActive(true)
            ->setDescription('aa-first-description')
            ->setAge(10)
            ->setCompany("companyABC");
    }
    public static function ComplexObjectSecond(): ComplexObject
    {
        return (new ComplexObject())
            ->setId('bb-second-id-' . uniqid())
            ->setName('bb-second-name')
            ->setActive(false)
            ->setDescription('bb-second-description')
            ->setAge(20)
            ->setCompany("companyABC");
    }
    public static function ComplexObjectThird(): ComplexObject
    {
        return (new ComplexObject())
            ->setId('cc-third-id-' . uniqid())
            ->setName('cc-third-name')
            ->setActive(false)
            ->setDescription('cc-third-description')
            ->setAge(666)
            ->setCompany("evil-company");
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
                new ComplexFilter('companyABC'),
                2
            ],
            'complexDoubleFilter' => [
                new ComplexDoubleFilter('aa-first-name', 'companyABC'),
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
            'sorterObjectASC' => [
                new NameSorter()
            ],
            'sorterObjectDESC' => [
                new NameSorterDesc(),
                'desc'
            ],
            'closure' => [
                function (ComplexObject $a, ComplexObject $b) {
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
    public function testCanRetrieveSorted(Sorter|Closure $sorter, $order = 'asc')
    {
        $first = $this->ComplexObjectFirst();
        $second = $this->ComplexObjectSecond();
        $this->highPerformanceRepository->saveObject($first, $first->getId());
        $this->highPerformanceRepository->saveObject($second, $second->getId());
        $result = $this->highPerformanceRepository->findAllObjectSorted($sorter);
        $this->assertCount(2, $result);
        if($order === 'asc') {
            $this->assertEquals($first, $result[0]);
            $this->assertEquals($second, $result[1]);
        }else{
            $this->assertEquals($first, $result[1]);
            $this->assertEquals($second, $result[0]);
        }
    }


    public static function FilterSorter() : array
    {
        $saveObjects = [self::ComplexObjectFirst(), self::ComplexObjectSecond(), self::ComplexObjectThird()];
        return [
            'filterSorterObjectASC' => [
                $saveObjects,
                ["aa-first-name", "bb-second-name"],
                new ComplexFilter('companyABC'),
                2,
                new NameSorter()
            ],
            'filterSorterObjectDESC' => [
                $saveObjects,
                ["bb-second-name", "aa-first-name"],
                new ComplexFilter('companyABC'),
                2,
                new NameSorterDesc()
            ],
            'closureFastfilterSorterObjectDESC' => [
                $saveObjects,
                ["bb-second-name", "aa-first-name"],
                new ClosureFastFilter(
                    function (ComplexObject $object) {
                        return $object->getName() === 'aa-first-name' || $object->getName() === 'bb-second-name';
                    },
                    ['company' => 'companyABC']
                ),
                2,
                new NameSorterDesc()
            ],
            'closureDESC' => [
                $saveObjects,
                ["cc-third-name", "bb-second-name", "aa-first-name"],
                new AllFilter(),
                3,
                function (ComplexObject $a, ComplexObject $b) {
                    return strcmp($b->getName(), $a->getName());
                }
            ]
        ];
    }

    /**
     * @dataProvider FilterSorter
     * @param SimpleObject[] $toSave
     * @param SimpleObject[] $expectedNamesOrder
     * @param Filter|Closure $filter
     * @param int $expectedCount
     * @param Sorter|Closure $sorter
     * @return void
     */
    public function testCanRetrieveMatchedSorted(array $toSave, array $expectedNamesOrder, Filter|Closure $filter, int $expectedCount, Sorter|Closure $sorter)
    {
        foreach($toSave as $object) {
            $this->highPerformanceRepository->saveObject($object, $object->getId());
        }
        $result = $this->highPerformanceRepository->findMatchingFilterObjectSorted($filter,$sorter);
        $this->assertCount($expectedCount, $result);

        for($i = 0; $i < $expectedCount; $i++) {
            $this->assertEquals($expectedNamesOrder[$i], $result[$i]->getName());
        }
    }


    public function testQueryStyle()
    {
        $toSave = [self::ComplexObjectFirst(), self::ComplexObjectSecond(), self::ComplexObjectThird()];
        foreach($toSave as $object) {
            $this->highPerformanceRepository->saveObject($object, $object->getId());
        }

        $query = (new QueryBuilder())->where()
            ->condition('name', '=', 'aa-first-name')
            ->condition('company', '=', 'companyABC')
            ->end();
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals('aa-first-name', $result[0]->getName());
        $this->assertEquals('companyABC', $result[0]->getCompany());;


        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'bb-second-name')
            ->endX()
            ->condition('company', '=', 'companyABC')
            ->end();

        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);

        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'bb-second-name')
                ->condition('name', '=', 'cc-third-name')
            ->endX()
            ->condition('company', '=', 'companyABC')
            ->end();

        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);

        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'bb-second-name')
                ->condition('name', '=', 'cc-third-name')
            ->endX()
            ->condition('company', '=', 'evil-company')
            ->end();

        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(1, $result);

        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'bb-second-name')
                ->condition('name', '=', 'cc-third-name')
            ->endX()
            ->end();

        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(3, $result);

        $query = (new QueryBuilder())->where()
            ->condition('age', '>', 12)
            ->end();

        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);

    }

    public function testQueryStyleWithSorting()
    {
        $toSave = [self::ComplexObjectFirst(), self::ComplexObjectSecond(), self::ComplexObjectThird()];
        foreach($toSave as $object) {
            $this->highPerformanceRepository->saveObject($object, $object->getId());
        }

        $query = (new QueryBuilder())->where()
            ->condition('company', '=', 'companyABC')
            ->end()
            ->orderBy('id', 'asc');
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[1]->getId(), $result[1]->getId());


        $query = (new QueryBuilder())->where()
            ->condition('company', '=', 'companyABC')
            ->end()
            ->orderBy('id', 'desc');
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[1]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[0]->getId(), $result[1]->getId());



        $query = (new QueryBuilder())->where()
            ->condition('company', '=', 'companyABC')
            ->end()
            ->orderBy('name', 'asc');
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[1]->getId(), $result[1]->getId());


        $query = (new QueryBuilder())->where()
            ->condition('company', '=', 'companyABC')
            ->end()
            ->orderBy('name', SortOrder::DESC);
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[1]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[0]->getId(), $result[1]->getId());



        $query = (new QueryBuilder())->orderBy("company", SortOrder::ASC)->orderBy("id", SortOrder::ASC);;;
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(3, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[1]->getId(), $result[1]->getId());
        $this->assertEquals($toSave[2]->getId(), $result[2]->getId());



        $query = (new QueryBuilder())->orderBy("company", SortOrder::ASC)->orderBy("id", SortOrder::DESC);;;
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(3, $result);
        $this->assertEquals($toSave[1]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[0]->getId(), $result[1]->getId());
        $this->assertEquals($toSave[2]->getId(), $result[2]->getId());


    }

    public function testQueryStyleWithLimit()
    {
        $toSave = [self::ComplexObjectFirst(), self::ComplexObjectSecond(), self::ComplexObjectThird()];
        foreach($toSave as $object) {
            $this->highPerformanceRepository->saveObject($object, $object->getId());
        }

        $query = (new QueryBuilder())->where()
            ->condition('name', '=', 'aa-first-name')
            ->end()
            ->orderBy('id', 'asc')->limit(1);;
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());

        $query = (new QueryBuilder())->where()
            ->condition('age', '>', 12)
            ->end();
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);

        $query = (new QueryBuilder())->where()
            ->condition('age', Operation::GT, 12)
            ->end()
            ->orderBy('age', 'asc')->limit(1)->offset(0);
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals($toSave[1]->getId(), $result[0]->getId());

        $query = (new QueryBuilder())->where()
            ->condition('age', Operation::GT, 12)
            ->end()
            ->orderBy('age', 'asc')->limit(1)->offset(1);
        $result = $this->highPerformanceRepository->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals($toSave[2]->getId(), $result[0]->getId());

    }

}