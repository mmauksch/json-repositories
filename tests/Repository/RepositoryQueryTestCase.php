<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Mmauksch\JsonRepositories\Repository\HighPerformanceJsonRepository;
use Mmauksch\JsonRepositories\Tests\TestConstants;
use Mmauksch\JsonRepositories\Tests\TestObjects\CompanyObject;
use Mmauksch\JsonRepositories\Tests\TestObjects\ComplexObject;
use Mmauksch\JsonRepositories\Tests\TestObjects\CountryObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

abstract class RepositoryQueryTestCase extends TestCase
{
    protected static string $repodir = 'complex';
    protected static string $repodirCompany = 'company';
    protected static string $repodirCountry = 'country';
    protected static ?string $testdatapath = null;
    protected static Filesystem $filesystem;
    protected HighPerformanceJsonRepository $personRepository;

    protected HighPerformanceJsonRepository $companyRepository;
    protected HighPerformanceJsonRepository $countryRepository;
    public static function setUpBeforeClass(): void
    {
        self::$filesystem = new Filesystem();
        self::$testdatapath = __DIR__.'/../testdata/querytests/';
        self::$filesystem->mkdir(self::$testdatapath);
    }
    protected function setUp(): void
    {
        $repositoryDir = Path::join(self::$testdatapath, static::$repodir);
        self::$filesystem->mkdir($repositoryDir);
        $this->personRepository = new HighPerformanceJsonRepository(
            self::$testdatapath,
            self::$repodir,
            ComplexObject::class,
            self::$filesystem,
            TestConstants::JsonSerializer(),
            ['name', 'company']
        );
        $this->personRepository->reindex();

        $repositoryDir = Path::join(self::$testdatapath, static::$repodirCompany);
        self::$filesystem->mkdir($repositoryDir);
        $this->companyRepository = new HighPerformanceJsonRepository(
            self::$testdatapath,
            self::$repodirCompany,
            CompanyObject::class,
            self::$filesystem,
            TestConstants::JsonSerializer(),
            ['name', 'country']
        );
        $this->companyRepository->reindex();

        $repositoryDir = Path::join(self::$testdatapath, static::$repodirCountry);
        self::$filesystem->mkdir($repositoryDir);
        $this->countryRepository = new HighPerformanceJsonRepository(
            self::$testdatapath,
            self::$repodirCountry,
            CountryObject::class,
            self::$filesystem,
            TestConstants::JsonSerializer(),
            []
        );
        $this->countryRepository->reindex();
    }
}