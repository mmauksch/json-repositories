<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Mmauksch\JsonRepositories\Contract\Filter;
use Mmauksch\JsonRepositories\Repository\GenericJsonRepository;
use Mmauksch\JsonRepositories\Tests\TestConstants;
use Mmauksch\JsonRepositories\Tests\TestObjects\SimpleObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

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

    public function ObjectFirst(): SimpleObject
    {
        return (new SimpleObject())
            ->setId('first-id-' . uniqid())
            ->setName('first-name-' . uniqid());
    }
    public function ObjectSecond(): SimpleObject
    {
        return (new SimpleObject())
            ->setId('second-id-' . uniqid())
            ->setName('second-name-' . uniqid());
    }

    protected function setUp(): void
    {
        $repositoryDir = Path::join(self::$temppath, static::$repodir);
        if(is_dir($repositoryDir))
            self::$filesystem->remove($repositoryDir);;
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

    public function testCanFindWithFilterObject()
    {
        $first = $this->ObjectFirst();
        $second = $this->ObjectSecond();
        $this->instance->saveObject($first, $first->getId());
        $this->instance->saveObject($second, $second->getId());
        $this->assertCount(2, $this->instance->findAllObjects());
        $this->assertCount(1, $this->instance->findMatchingFilter(new IdFilter($first->getId())));
    }

    public function testCanFindWithFilterObjectNotFound()
    {
        $first = $this->ObjectFirst();
        $second = $this->ObjectSecond();
        $this->instance->saveObject($first, $first->getId());
        $this->instance->saveObject($second, $second->getId());
        $this->assertCount(2, $this->instance->findAllObjects());
        $this->assertCount(0, $this->instance->findMatchingFilter(new IdFilter('not-existing-id')));
    }

    public function testCanFindWithFilterAsClosure()
    {
        $first = $this->ObjectFirst();
        $second = $this->ObjectSecond();
        $this->instance->saveObject($first, $first->getId());
        $this->instance->saveObject($second, $second->getId());
        $this->assertCount(2, $this->instance->findAllObjects());
        $this->assertCount(1, $this->instance->findMatchingFilter(
            function(SimpleObject $object) use ($first) {
                return $object->getId() === $first->getId();
            })
        );

    }
}

/**
 * @template T of SimpleObject
 * @implements Filter<SimpleObject>
 */
class IdFilter implements Filter
{
    private string $id;

    public function __construct(string $id) {
        $this->id = $id;
    }
    public function __invoke(object $object): bool
    {
        return $object->getId() === $this->id;
    }
}