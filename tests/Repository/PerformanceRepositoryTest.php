<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Closure;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Contract\Extensions\Sorter;
use Mmauksch\JsonRepositories\Filter\ClosureFastFilter;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\Operation;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\RefAttribute;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\SortOrder;
use Mmauksch\JsonRepositories\Filter\QueryStyle\QueryBuilder;
use Mmauksch\JsonRepositories\Repository\GenericJsonRepository;
use Mmauksch\JsonRepositories\Repository\HighPerformanceJsonRepository;
use Mmauksch\JsonRepositories\Tests\TestConstants;
use Mmauksch\JsonRepositories\Tests\TestObjects\CompanyObject;
use Mmauksch\JsonRepositories\Tests\TestObjects\ComplexObject;
use Mmauksch\JsonRepositories\Tests\TestObjects\CountryObject;
use Mmauksch\JsonRepositories\Tests\TestObjects\SimpleObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class PerformanceRepositoryTest extends TestCase
{
    protected static string $repodir = 'complex';
    protected static string $repodirCompany = 'company';
    protected static string $repodirCountry = 'country';
    protected static ?string $temppath = null;
    protected static Filesystem $filesystem;
    private HighPerformanceJsonRepository $personRepository;

    private HighPerformanceJsonRepository $companyRepository;
    private HighPerformanceJsonRepository $countryRepository;

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

    public static function Person1(): ComplexObject
    {
        return (new ComplexObject())
            ->setId('aa-first-id')
            ->setName('aa-first-name')
            ->setActive(true)
            ->setDescription('aa-first-description')
            ->setAge(10)
            ->setCompany("companyABC");
    }
    public static function Person2(): ComplexObject
    {
        return (new ComplexObject())
            ->setId('bb-second-id')
            ->setName('bb-second-name')
            ->setActive(false)
            ->setDescription('bb-second-description')
            ->setAge(20)
            ->setCompany("companyABC");
    }
    public static function Person3(): ComplexObject
    {
        return (new ComplexObject())
            ->setId('cc-third-id')
            ->setName('cc-third-name')
            ->setActive(false)
            ->setDescription('cc-third-description')
            ->setAge(666)
            ->setCompany("evil-company");
    }
    public static function Person4(): ComplexObject
    {
        return (new ComplexObject())
            ->setId('dd-fourth-id')
            ->setName('dd-fourth-name')
            ->setActive(false)
            ->setDescription('dd-fourth-description')
            ->setAge(34)
            ->setCompany("companyZZZ");
    }

    public static function Company1(): CompanyObject
    {
        return (new CompanyObject())
            ->setName('companyABC')
            ->setAddress('abc-address')
            ->setCountry('de')
            ->setCity('a-city')
            ->setBoss('bb-second-id');
    }
    public static function Company2(): CompanyObject
    {
        return (new CompanyObject())
            ->setName('evil-company')
            ->setAddress('666-address')
            ->setCountry('hell')
            ->setCity('666-city')
            ->setBoss('cc-third-id');
    }
    public static function Company3(): CompanyObject
    {
        return (new CompanyObject())
            ->setName('companyZZZ')
            ->setAddress('zzz-address')
            ->setCountry('de')
            ->setCity('a-city')
            ->setBoss('dd-fourth-id');
    }
    public static function Country1(): CountryObject
    {
        return (new CountryObject())
            ->setShort('hell')
            ->setLong('Hellfiretanien')
            ->setDescription('in all, a funny place')
            ->setOverlord('cc-third-id');
    }
    public static function Country2(): CountryObject
    {
        return (new CountryObject())
            ->setShort('de')
            ->setLong('germany')
            ->setDescription('germans live here')
            ->setOverlord('bb-second-id');
    }


    protected function setUp(): void
    {
        $repositoryDir = Path::join(self::$temppath, static::$repodir);
        if(is_dir($repositoryDir))
            self::$filesystem->remove($repositoryDir);
        self::$filesystem->mkdir($repositoryDir);
        $this->personRepository = new HighPerformanceJsonRepository(
            self::$temppath,
            self::$repodir,
            ComplexObject::class,
            self::$filesystem,
            TestConstants::JsonSerializer(),
            ['name', 'company']
        );

        $repositoryDir = Path::join(self::$temppath, static::$repodirCompany);
        if(is_dir($repositoryDir))
            self::$filesystem->remove($repositoryDir);
        self::$filesystem->mkdir($repositoryDir);
        $this->companyRepository = new HighPerformanceJsonRepository(
            self::$temppath,
            self::$repodirCompany,
            CompanyObject::class,
            self::$filesystem,
            TestConstants::JsonSerializer(),
            ['name', 'country']
        );
        $repositoryDir = Path::join(self::$temppath, static::$repodirCountry);
        if(is_dir($repositoryDir))
            self::$filesystem->remove($repositoryDir);
        self::$filesystem->mkdir($repositoryDir);
        $this->countryRepository = new HighPerformanceJsonRepository(
            self::$temppath,
            self::$repodirCountry,
            CountryObject::class,
            self::$filesystem,
            TestConstants::JsonSerializer(),
            []
        );

    }

    public function testCanStoreAndRetrieveSimpleObjectHP()
    {
        $testObject = $this->Person1();
        $id = $testObject->getId();
        $this->assertFileDoesNotExist(Path::join(self::$temppath, static::$repodir, "$id.json"));
        $this->personRepository->saveObject($testObject, $id);
        $this->assertTrue(self::$filesystem->exists(Path::join(self::$temppath, static::$repodir, "$id.json")));
        $loaded = $this->personRepository->findObjectById($id);
        $this->assertNotSame($testObject, $loaded);
        $this->assertEquals($testObject, $loaded);
    }

    public function testWillReturnNullIfObjectNotFound()
    {
        $this->assertNull($this->personRepository->findObjectById('not-existing-id'));
    }

    public function testCanStoreMultipleObjects()
    {
        $testObjectFirst = $this->Person1();
        $testObjectSecond = $this->Person2();
        $this->personRepository->saveObject($testObjectFirst, $testObjectFirst->getId());
        $this->personRepository->saveObject($testObjectSecond, $testObjectSecond->getId());
        $this->assertFileExists(Path::join(self::$temppath, static::$repodir, "{$testObjectFirst->getId()}.json"));
        $this->assertFileExists(Path::join(self::$temppath, static::$repodir, "{$testObjectSecond->getId()}.json"));
        $all = $this->personRepository->findAllObjects();
        $this->assertCount(2, $all);
    }

    public function testCanDeleteObject()
    {
        $testObject = $this->Person1();
        $id = $testObject->getId();
        $this->personRepository->saveObject($testObject, $id);
        $this->assertTrue(self::$filesystem->exists(Path::join(self::$temppath, static::$repodir, "$id.json")));
        $this->personRepository->deleteObjectById($id);
        $this->assertFalse(self::$filesystem->exists(Path::join(self::$temppath, static::$repodir, "$id.json")));
    }

    public function testDeleteWillNotFailIfObjectDoesNotExist()
    {
        $this->assertFileDoesNotExist(Path::join(self::$temppath, static::$repodir, "not-existing-id.json"));
        $this->personRepository->deleteObjectById('not-existing-id');
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
        $first = $this->Person1();
        $second = $this->Person2();
        $this->personRepository->saveObject($first, $first->getId());
        $this->personRepository->saveObject($second, $second->getId());
        $this->assertCount(2, $this->personRepository->findAllObjects());
        $this->assertCount($expectedCount, $this->personRepository->findMatchingFilter($filter));
    }


    public function testCanFindWithFilterObjectNotFound()
    {
        $first = $this->Person1();
        $second = $this->Person2();
        $this->personRepository->saveObject($first, $first->getId());
        $this->personRepository->saveObject($second, $second->getId());
        $this->assertCount(2, $this->personRepository->findAllObjects());
        $this->assertCount(0, $this->personRepository->findMatchingFilter(new NameFilter('not-existing-name')));
    }

    public function testCanFindWithFilterAsClosure()
    {
        $first = $this->Person1();
        $second = $this->Person2();
        $this->personRepository->saveObject($first, $first->getId());
        $this->personRepository->saveObject($second, $second->getId());
        $this->assertCount(2, $this->personRepository->findAllObjects());
        $resultMatching = $this->personRepository->findMatchingFilter(
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
        $first = $this->Person1();
        $second = $this->Person2();
        $this->personRepository->saveObject($first, $first->getId());
        $this->personRepository->saveObject($second, $second->getId());
        $result = $this->personRepository->findAllObjectSorted($sorter);
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
        $saveObjects = [self::Person1(), self::Person2(), self::Person3()];
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
            $this->personRepository->saveObject($object, $object->getId());
        }
        $result = $this->personRepository->findMatchingFilterObjectSorted($filter,$sorter);
        $this->assertCount($expectedCount, $result);

        for($i = 0; $i < $expectedCount; $i++) {
            $this->assertEquals($expectedNamesOrder[$i], $result[$i]->getName());
        }
    }


    public function testQueryStyle()
    {
        $toSave = [self::Person1(), self::Person2(), self::Person3()];
        foreach($toSave as $object) {
            $this->personRepository->saveObject($object, $object->getId());
        }

        $query = (new QueryBuilder())->where()
            ->condition('name', '=', 'aa-first-name')
            ->condition('company', '=', 'companyABC')
            ->end();
        $result = $this->personRepository->findMatchingFilter($query);
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

        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);

        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'bb-second-name')
                ->condition('name', '=', 'cc-third-name')
            ->endX()
            ->condition('company', '=', 'companyABC')
            ->end();

        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);

        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'bb-second-name')
                ->condition('name', '=', 'cc-third-name')
            ->endX()
            ->condition('company', '=', 'evil-company')
            ->end();

        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(1, $result);

        $query = (new QueryBuilder())->where()
            ->orX()
                ->condition('name', '=', 'aa-first-name')
                ->condition('name', '=', 'bb-second-name')
                ->condition('name', '=', 'cc-third-name')
            ->endX()
            ->end();

        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(3, $result);

        $query = (new QueryBuilder())->where()
            ->condition('age', '>', 12)
            ->end();

        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);

    }

    public function testQueryStyleWithSorting()
    {
        $toSave = [self::Person1(), self::Person2(), self::Person3()];
        foreach($toSave as $object) {
            $this->personRepository->saveObject($object, $object->getId());
        }

        $query = (new QueryBuilder())->where()
            ->condition('company', '=', 'companyABC')
            ->end()
            ->orderBy('id', 'asc');
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[1]->getId(), $result[1]->getId());


        $query = (new QueryBuilder())->where()
            ->condition('company', '=', 'companyABC')
            ->end()
            ->orderBy('id', 'desc');
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[1]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[0]->getId(), $result[1]->getId());



        $query = (new QueryBuilder())->where()
            ->condition('company', '=', 'companyABC')
            ->end()
            ->orderBy('name', 'asc');
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[1]->getId(), $result[1]->getId());


        $query = (new QueryBuilder())->where()
            ->condition('company', '=', 'companyABC')
            ->end()
            ->orderBy('name', SortOrder::DESC);
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);
        $this->assertEquals($toSave[1]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[0]->getId(), $result[1]->getId());



        $query = (new QueryBuilder())->orderBy("company", SortOrder::ASC)->orderBy("id", SortOrder::ASC);;;
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(3, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[1]->getId(), $result[1]->getId());
        $this->assertEquals($toSave[2]->getId(), $result[2]->getId());



        $query = (new QueryBuilder())->orderBy("company", SortOrder::ASC)->orderBy("id", SortOrder::DESC);;;
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(3, $result);
        $this->assertEquals($toSave[1]->getId(), $result[0]->getId());
        $this->assertEquals($toSave[0]->getId(), $result[1]->getId());
        $this->assertEquals($toSave[2]->getId(), $result[2]->getId());


    }

    public function testQueryStyleWithLimit()
    {
        $toSave = [self::Person1(), self::Person2(), self::Person3()];
        foreach($toSave as $object) {
            $this->personRepository->saveObject($object, $object->getId());
        }

        $query = (new QueryBuilder())->where()
            ->condition('name', '=', 'aa-first-name')
            ->end()
            ->orderBy('id', 'asc')->limit(1);;
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals($toSave[0]->getId(), $result[0]->getId());

        $query = (new QueryBuilder())->where()
            ->condition('age', '>', 12)
            ->end();
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(2, $result);

        $query = (new QueryBuilder())->where()
            ->condition('age', Operation::GT, 12)
            ->end()
            ->orderBy('age', 'asc')->limit(1)->offset(0);
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals($toSave[1]->getId(), $result[0]->getId());

        $query = (new QueryBuilder())->where()
            ->condition('age', Operation::GT, 12)
            ->end()
            ->orderBy('age', 'asc')->limit(1)->offset(1);
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(1, $result);
        $this->assertEquals($toSave[2]->getId(), $result[0]->getId());

    }

    private function runTestJoinQuery($query): iterable
    {
        $toSavePerson = [self::Person1(), self::Person2(), self::Person3(), self::Person4()];
        foreach($toSavePerson as $object) {
            $this->personRepository->saveObject($object, $object->getId());
        }
        $toSaveCompany= [self::Company1(), self::Company2(), self::Company3()];
        foreach($toSaveCompany as $object) {
            $this->companyRepository->saveObject($object, $object->getName());
        }
        $toSaveCountries= [self::Country1(), self::Country2()];
        foreach($toSaveCountries as $object) {
            $this->countryRepository->saveObject($object, $object->getShort());
        }
        return $this->personRepository->findMatchingFilter($query);
    }

    public function testSimpleInnerJoin()
    {
        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "com")
            ->On("com.name", "company")
            ->where()
            ->condition('com.city', '=', 'a-city')
            ->end();
        $this->assertCount(3, $this->runTestJoinQuery($query));
    }

    public function testSimpleJoinWithMultipleConditions()
    {
        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "com")
            ->On("com.name", "company")
            ->where()
            ->condition('com.city', '=', 'a-city')
            ->condition('age', '<', 20)
            ->end();
        $this->assertCount(1, $this->runTestJoinQuery($query));
    }

    public function testSimpleJoinWithConditionsNoResultsOnFalseConditions() {
        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "com")
            ->On("com.name", "company")
            ->where()
            ->condition('com.city', '=', 'a-city')
            ->condition('com.address', '=', 'voelligFalsch')
            ->condition('age', '<', 20)
            ->end();
        $this->assertCount(0, $this->runTestJoinQuery($query));
    }

    public function testNestedJoinWithConditionOnJoinedRepository() {
        $query = (new QueryBuilder())
            ->innerJoin($this->countryRepository, "country")
            ->On("company.country", "country.short")
            ->innerJoin($this->companyRepository, "company")
            ->On("company.name", "company")
            ->where()
            ->condition('country.long', '=', 'germany')
            ->end();
        $this->assertCount(3, $this->runTestJoinQuery($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->countryRepository, "country")
            ->On("company.country", "country.short")
            ->innerJoin($this->companyRepository, "company")
            ->On("company.name", "company")
            ->where()
            ->condition('country.long', '=', 'Hellfiretanien')
            ->end();
        $this->assertCount(1, $this->runTestJoinQuery($query));
    }

    public function testJoinWithReferenceInCondition()
    {
        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "company")
            ->On("company", "company.name")
            ->innerJoin($this->countryRepository, "country")
            ->On("country.short", "company.country")
            ->where()
            ->condition('country.overlord', '=', RefAttribute::fromString('company.boss'))
            ->end();
        $this->assertCount(3, $this->runTestJoinQuery($query));
    }

    public function testINConditions()
    {
        $query = (new QueryBuilder())
            ->where()
            ->condition('name', Operation::IN, ['nopeName', 'cc-third-name', 'aa-first-name'])
            ->end();
        $this->assertCount(2, $this->runTestJoinQuery($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "company")
            ->On("company", "company.name")
            ->innerJoin($this->countryRepository, "country")
            ->On("country.short", "company.country")
            ->where()
            ->condition('country.long', Operation::IN, ['nopeTan', 'germany'])
            ->end();
        $this->assertCount(3, $this->runTestJoinQuery($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "company")
            ->On("company", "company.name")
            ->innerJoin($this->countryRepository, "country")
            ->On("country.short", "company.country")
            ->where()
            ->condition('country.description', Operation::IN, ['in all, a funny place'])
            ->end();
        $this->assertCount(1, $this->runTestJoinQuery($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "company")
            ->On("company", "company.name")
            ->innerJoin($this->countryRepository, "country")
            ->On("country.short", "company.country")
            ->where()
            ->condition('country.long', Operation::NOT_IN, ['nopeTan', 'germany'])
            ->end();
        $this->assertCount(1, $this->runTestJoinQuery($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "company")
            ->On("company", "company.name")
            ->innerJoin($this->countryRepository, "country")
            ->On("country.short", "company.country")
            ->where()
            ->condition('country.long', Operation::NOT_IN, ['nopeTan', 'germany', 'Hellfiretanien'])
            ->end();
        $this->assertCount(0, $this->runTestJoinQuery($query));


    }


}