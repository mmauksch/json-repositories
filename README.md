# JSON File Repository

A lightweight PHP library implementing the repository pattern with JSON file persistence. This library provides an easy way to create repositories that store data in JSON files, making it ideal for integration tests, Symfony web tests, and application prototyping.

## 📋 Overview

JSON File Repository offers a simple solution for temporary data persistence without the need for a full database setup. It implements the standard repository pattern, providing familiar interfaces for storing, retrieving, and managing collections of objects.

⚠️ **Note:** This library is intended for testing and prototyping purposes only and is not suitable for production workloads.

## 🚀 Installation

Install the library via Composer:

```bash
composer require mmauksch/json-repositories
```

## 🔧 Usage

### Basic Usage

```php
// Set up the repository
$repository = new GenericJsonRepository(
    '/path/to/storage',     // Base storage path
    'entity-directory',     // Subdirectory for this entity type
    SimpleObject::class,    // The entity class
    new Filesystem(),       // Symfony Filesystem component
    $serializer             // Symfony Serializer for JSON conversion
);

// Create and save an object
$object = new SimpleObject();
$object->setId('unique-id');
$object->setName('Object Name');
$repository->saveObject($object, $object->getId());

// Find an object by ID
$foundObject = $repository->findObjectById('unique-id');

// Get all objects
$allObjects = $repository->findAllObjects();

// Delete an object
$repository->deleteObjectById('unique-id');
```

### Extending AbstractRepository

Instead of using GenericJsonRepository as an instance, you can also extend the AbstractJsonRepository class to create your own repository implementation:

```php
class UserRepository extends AbstractJsonRepository 
{
    public function __construct(string $jsonDbBaseDir, Filesystem $filesystem, SerializerInterface $serializer)
    {
        parent::__construct($jsonDbBaseDir, 'users', User::class, $filesystem, $serializer);
    }

    // Add custom methods specific to your domain
    public function findByUsername(string $username): ?User
    {
        return $this->findMatchingFilter(function(User $user) use ($username) {
            return $user->getUsername() === $username;
        })[0] ?? null;
    }
}
```

### Using Traits Selectively

You can also build your own repository implementation by selectively importing the traits that provide specific functionality:

```php
class CustomRepository implements BasicJsonRepository 
{
    // Only include the basic CRUD operations
    use BasicJsonRepositoryTrait;

    // You can add the other traits as needed:
    // use FilterableJsonRepositoryTrait; 
    // use SortableJsonRepositoryTrait;

    private string $basePath;
    protected string $targetClass;

    public function __construct(string $basePath, string $targetClass, Filesystem $filesystem, SerializerInterface $serializer)
    {
        $this->basePath = $basePath;
        $this->targetClass = $targetClass;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
    }

    protected function objectStoreDirectory(): string
    {
        return $this->basePath;
    }
}
```

### Using Filters

```php
// Find objects matching a filter using a closure
$filteredObjects = $repository->findMatchingFilter(function (SimpleObject $object) {
    return $object->getName() === 'Object Name';
});

// Or implement a Filter class
class NameFilter implements Filter {
    private string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function matches(object $object): bool {
        return $object->getName() === $this->name;
    }
}

$filteredObjects = $repository->findMatchingFilter(new NameFilter('Object Name'));
```

### Using Sorters

```php
// Sort objects using a closure
$sortedObjects = $repository->findAllObjectSorted(function (SimpleObject $a, SimpleObject $b) {
    return strcmp($a->getName(), $b->getName());
});

// Or implement a Sorter class
class NameSorter implements Sorter {
    public function compare(object $a, object $b): int {
        return strcmp($a->getName(), $b->getName());
    }
}

$sortedObjects = $repository->findAllObjectSorted(new NameSorter());
```

### Using with Symfony Tests

```php
// In your test class
protected static string $repodir = 'entities';
protected static ?string $temppath = null;
protected static Filesystem $filesystem;
private GenericJsonRepository $repository;

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

protected function setUp(): void
{
    $repositoryDir = Path::join(self::$temppath, static::$repodir);
    if(is_dir($repositoryDir))
        self::$filesystem->remove($repositoryDir);

    // Create a clean repository for each test
    $this->repository = new GenericJsonRepository(
        self::$temppath,
        self::$repodir,
        Entity::class,
        self::$filesystem,
        $this->getSerializer()
    );
}

private function getSerializer(): SerializerInterface
{
    return new Serializer(
        [new ObjectNormalizer(
            null, null, null,
            new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()])
        ), new ArrayDenormalizer()],
        [new JsonEncoder()]
    );
}
```

### For Application Prototyping

```php
// In your service configuration (Symfony)
public function configureServices(ContainerBuilder $container)
{
    // ...

    // Setup serializer if not already configured
    $container->register('app.json_serializer', Serializer::class)
        ->setArguments([
            [new Reference('serializer.normalizer.object'), new Reference('serializer.normalizer.array')],
            [new Reference('serializer.encoder.json')]
        ]);

    // Register the repository
    $container->register(EntityRepositoryInterface::class)
        ->setClass(GenericJsonRepository::class)
        ->setArguments([
            '%kernel.project_dir%/var/storage',  // Base path
            'entities',                         // Entity directory
            Entity::class,                      // Entity class
            new Reference('filesystem'),        // Symfony Filesystem
            new Reference('app.json_serializer') // Serializer
        ]);
}
```

You can also simply let the dependency injection container provide the filesystem and serializer. In Symfony YAML configuration:

```yaml
# config/services.yaml
services:
    # User repository with autowiring
    App\Repository\UserRepository:
        class: GenericJsonRepository
        arguments:
            $jsonDbBaseDir: '%kernel.project_dir%/var/storage'
            $dirName: 'users'
            $targetClass: 'App\Entity\User'
        # The filesystem and serializer will be automatically injected
        autowire: true
```
```

## 🔄 Key Features

- **Simple Repository Pattern Implementation** - Follows standard repository interfaces
- **JSON File Persistence** - Stores entities as JSON files without database setup
- **Flexible Storage Location** - Configure where your JSON files are stored
- **Advanced Filtering** - Filter entities using both Filter objects and closures
- **Custom Sorting** - Sort entities using Sorter objects or custom comparison closures
- **Type Safety** - Repositories are typed to specific entity classes
- **Symfony Integration** - Works with Symfony Filesystem and Serializer components

## 💡 Best Practices

- Use a dedicated directory for test storage that is cleared between test runs
- For integration tests, create fresh repositories in your test setup
- Implement proper cleanup in tearDown() methods or use a test framework's isolation features
- Consider using memory-only repositories for very simple tests

## 🧪 When to Use

- ✅ Writing integration tests
- ✅ Developing Symfony web tests
- ✅ Prototyping applications or features
- ✅ Demos and proof of concepts
- ❌ Production applications
- ❌ High-performance requirements
- ❌ Applications with complex database requirements

## 📄 License

This project is licensed under the MIT License - see the LICENSE.md file for details.

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to check the issues page.