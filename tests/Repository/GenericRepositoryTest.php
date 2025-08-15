<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\SortOrder;
use Mmauksch\JsonRepositories\Filter\QueryStyle\QueryBuilder;
use Mmauksch\JsonRepositories\Repository\GenericJsonRepository;
use Mmauksch\JsonRepositories\Tests\TestConstants;
use Mmauksch\JsonRepositories\Tests\TestObjects\SimpleObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use function PHPUnit\Framework\assertEquals;

class GenericRepositoryTest extends TestCase
{
    protected static string $repodir = 'simple';
    protected static ?string $temppath = null;
    protected static Filesystem $filesystem;
    private GenericJsonRepository $instance;

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

    public static function ObjectFirst(): SimpleObject
    {
        return (new SimpleObject())
            ->setId('a-first-id-' . uniqid())
            ->setName('aa-first-name');
    }
    public static function ObjectSecond(): SimpleObject
    {
        return (new SimpleObject())
            ->setId('b-second-id-' . uniqid())
            ->setName('bb-second-name');
    }

    public static function ObjectThird(): SimpleObject
    {
        return (new SimpleObject())
            ->setId('c-third-id-' . uniqid())
            ->setName('aa-first-name'); // same name as first
    }

    protected function setUp(): void
    {
        $repositoryDir = Path::join(self::$temppath, static::$repodir);
        if(is_dir($repositoryDir))
            self::$filesystem->remove($repositoryDir);
        $this->instance = new GenericJsonRepository(
            self::$temppath,
            self::$repodir,
            SimpleObject::class,
            self::$filesystem,
            TestConstants::JsonSerializer()
        );
    }

    public function testCanStoreAndRetrieveSimpleObject()
    {
        $testObject = $this->ObjectFirst();
        $id = $testObject->getId();
        $this->assertFileDoesNotExist(Path::join(self::$temppath, static::$repodir, "$id.json"));
        $this->instance->saveObject($testObject, $id);
        $this->assertTrue(self::$filesystem->exists(Path::join(self::$temppath, static::$repodir, "$id.json")));
        $loaded = $this->instance->findObjectById($id);
        $this->assertNotSame($testObject, $loaded);
        $this->assertEquals($testObject, $loaded);
    }

    public function testWillReturnNullIfObjectNotFound()
    {
        $this->assertNull($this->instance->findObjectById('not-existing-id'));
    }

    public function testCanStoreMultipleObjects()
    {
        $testObjectFirst = $this->ObjectFirst();
        $testObjectSecond = $this->ObjectSecond();
        $this->instance->saveObject($testObjectFirst, $testObjectFirst->getId());
        $this->instance->saveObject($testObjectSecond, $testObjectSecond->getId());
        $this->assertFileExists(Path::join(self::$temppath, static::$repodir, "{$testObjectFirst->getId()}.json"));
        $this->assertFileExists(Path::join(self::$temppath, static::$repodir, "{$testObjectSecond->getId()}.json"));
        $all = $this->instance->findAllObjects();
        $this->assertCount(2, $all);
    }

    public function testCanDeleteObject()
    {
        $testObject = $this->ObjectFirst();
        $id = $testObject->getId();
        $this->instance->saveObject($testObject, $id);
        $this->assertTrue(self::$filesystem->exists(Path::join(self::$temppath, static::$repodir, "$id.json")));
        $this->instance->deleteObjectById($id);
        $this->assertFalse(self::$filesystem->exists(Path::join(self::$temppath, static::$repodir, "$id.json")));
    }

    public function testDeleteWillNotFailIfObjectDoesNotExist()
    {
        $this->assertFileDoesNotExist(Path::join(self::$temppath, static::$repodir, "not-existing-id.json"));
        $this->instance->deleteObjectById('not-existing-id');
        $this->assertTrue(true);
    }

    public static function FilterProvider() : array {
        return [
            'filterObject' => [
                new NameFilter('aa-first-name')
            ],
            'closure' => [
                function (SimpleObject $object) {
                    return $object->getName() === 'aa-first-name';
                }
            ]
        ];
    }


    /**
     * @dataProvider FilterProvider
     * @param Filter|Closure $filter
     * @return void
     */
    public function testCanFindWithFilter(Filter|Closure $filter)
    {
        $first = $this->ObjectFirst();
        $second = $this->ObjectSecond();
        $this->instance->saveObject($first, $first->getId());
        $this->instance->saveObject($second, $second->getId());
        $this->assertCount(2, $this->instance->findAllObjects());
        $this->assertCount(1, $this->instance->findMatchingFilter($filter));
    }

    public function testCanFindWithFilterObjectNotFound()
    {
        $first = $this->ObjectFirst();
        $second = $this->ObjectSecond();
        $this->instance->saveObject($first, $first->getId());
        $this->instance->saveObject($second, $second->getId());
        $this->assertCount(2, $this->instance->findAllObjects());
        $this->assertCount(0, $this->instance->findMatchingFilter(new NameFilter('not-existing-name')));
    }

    public function testCanFindWithFilterAsClosure()
    {
        $first = $this->ObjectFirst();
        $second = $this->ObjectSecond();
        $this->instance->saveObject($first, $first->getId());
        $this->instance->saveObject($second, $second->getId());
        $this->assertCount(2, $this->instance->findAllObjects());
        $resultMatching = $this->instance->findMatchingFilter(
            function (SimpleObject $object) use ($first) {
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
    public function testCanRetrieveSorted(Sorter|Closure $sorter, $order = 'asc')
    {
        $first = $this->ObjectFirst();
        $second = $this->ObjectSecond();
        $this->instance->saveObject($first, $first->getId());
        $this->instance->saveObject($second, $second->getId());
        $result = $this->instance->findAllObjectSorted($sorter);
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
        $saveObjects = [self::ObjectFirst(), self::ObjectSecond(), self::ObjectThird()];
        return [
            'filterSorterObjectASC' => [
                $saveObjects,
                ["aa-first-name", "aa-first-name"],
                new NameFilter('aa-first-name'),
                2,
                new IdSorter()
            ],
            'filterSorterObjectDESC' => [
                $saveObjects,
                ["aa-first-name", "aa-first-name"],
                new NameFilter('aa-first-name'),
                2,
                new IdSorterDesc()
            ],
            'closureDESC' => [
                $saveObjects,
                ["bb-second-name", "aa-first-name", "aa-first-name"],
                new AllFilter(),
                3,
                function (SimpleObject $a, SimpleObject $b) {
                    return strcmp($b->getName(), $a->getName());
                }
            ]
        ];
    }

    /**
     * @dataProvider FilterSorter
     * @param SimpleObject[] $toSave
     * @param SimpleObject[] $expectedNameOrder
     * @param Filter|Closure $filter
     * @param int $expectedCount
     * @param Sorter|Closure $sorter
     * @return void
     */
    public function testCanRetrieveMatchedSorted(array $toSave, array $expectedNameOrder, Filter|Closure $filter, int $expectedCount, Sorter|Closure $sorter)
    {
        foreach($toSave as $object) {
            $this->instance->saveObject($object, $object->getId());
        }
        $result = $this->instance->findMatchingFilterObjectSorted($filter,$sorter);
        $this->assertCount($expectedCount, $result);

        for($i = 0; $i < $expectedCount; $i++) {
            $this->assertEquals($expectedNameOrder[$i], $result[$i]->getName());
        }
    }


    public function testQueryStyle()
    {
        $toSave = [self::ObjectFirst(), self::ObjectSecond(), self::ObjectThird()];
        foreach($toSave as $object) {
            $this->instance->saveObject($object, $object->getId());
        }

        $query = (new QueryBuilder())->where()
            ->condition('id', '=', $toSave[0]->getId())
            ->end();
        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());


        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'bb-second-name')
            ->endX()
            ->end();

        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(3, $result);

        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'nope')
            ->endX()
            ->end();

        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(2, $result);

        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'bb-second-name')
                ->condition('name', '=', 'cc-third-name')
            ->endX()
            ->condition('id', '=', $toSave[0]->getId())
            ->end();

        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(1, $result);

        $query = (new QueryBuilder());
        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(3, $result);

    }


    public function testQueryStyleWithSorting()
    {
        $toSave = [self::ObjectFirst(), self::ObjectSecond(), self::ObjectThird()];
        foreach($toSave as $object) {
            $this->instance->saveObject($object, $object->getId());
        }

        $query = (new QueryBuilder())->where()
            ->condition('name', '=', 'aa-first-name')
            ->end()
            ->orderBy('id', 'asc');
        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[2]->getId(), $result[1]->getId());


        $query = (new QueryBuilder())->where()
            ->condition('name', '=', 'aa-first-name')
            ->end()
            ->orderBy('id', 'desc');
        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[2]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[0]->getId(), $result[1]->getId());

    }

    public function testQueryStyleWithLimit()
    {
        $toSave = [self::ObjectFirst(), self::ObjectSecond(), self::ObjectThird()];
        foreach($toSave as $object) {
            $this->instance->saveObject($object, $object->getId());
        }

        $query = (new QueryBuilder())->where()
            ->condition('name', '=', 'aa-first-name')
            ->end()
            ->orderBy('id', 'asc')->limit(1);;
        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());


        $query = (new QueryBuilder())->where()
            ->condition('name', '=', 'aa-first-name')
            ->end()
            ->orderBy('id', 'asc')->limit(1)->offset(1);
        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals($toSave[2]->getId(), $result[0]->getId());

        $query = (new QueryBuilder())
            ->orderBy('id', 'asc')->limit(2)->offset(1);
        $result = $this->instance->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[1]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[2]->getId(), $result[1]->getId());

    }


}