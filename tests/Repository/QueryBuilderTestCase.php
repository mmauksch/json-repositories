<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\Operation;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\RefAttribute;
use Mmauksch\JsonRepositories\Filter\QueryStyle\QueryBuilder;

class QueryBuilderTestCase extends RepositoryQueryTestCase
{

    public function testQueryStyle()
    {
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
        $this->assertCount(3, $result);
    }

    public function testSimpleInnerJoin()
    {
        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "com")
            ->On("com.name", "company")
            ->where()
            ->condition('com.city', '=', 'a-city')
            ->end();
        $this->assertCount(3, $this->personRepository->findMatchingFilter($query));
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
        $this->assertCount(1, $this->personRepository->findMatchingFilter($query));
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
        $this->assertCount(0, $this->personRepository->findMatchingFilter($query));
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
        $this->assertCount(3, $this->personRepository->findMatchingFilter($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->countryRepository, "country")
            ->On("company.country", "country.short")
            ->innerJoin($this->companyRepository, "company")
            ->On("company.name", "company")
            ->where()
            ->condition('country.long', '=', 'Hellfiretanien')
            ->end();
        $this->assertCount(1, $this->personRepository->findMatchingFilter($query));
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
        $this->assertCount(3, $this->personRepository->findMatchingFilter($query));
    }

    public function testINConditions()
    {
        $query = (new QueryBuilder())
            ->where()
            ->condition('name', Operation::IN, ['nopeName', 'cc-third-name', 'aa-first-name'])
            ->end();
        $this->assertCount(2, $this->personRepository->findMatchingFilter($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "company")
            ->On("company", "company.name")
            ->innerJoin($this->countryRepository, "country")
            ->On("country.short", "company.country")
            ->where()
            ->condition('country.long', Operation::IN, ['nopeTan', 'germany'])
            ->end();
        $this->assertCount(3, $this->personRepository->findMatchingFilter($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "company")
            ->On("company", "company.name")
            ->innerJoin($this->countryRepository, "country")
            ->On("country.short", "company.country")
            ->where()
            ->condition('country.description', Operation::IN, ['in all, a funny place'])
            ->end();
        $this->assertCount(1, $this->personRepository->findMatchingFilter($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "company")
            ->On("company", "company.name")
            ->innerJoin($this->countryRepository, "country")
            ->On("country.short", "company.country")
            ->where()
            ->condition('country.long', Operation::NOT_IN, ['nopeTan', 'germany'])
            ->end();
        $this->assertCount(1, $this->personRepository->findMatchingFilter($query));

        $query = (new QueryBuilder())
            ->innerJoin($this->companyRepository, "company")
            ->On("company", "company.name")
            ->innerJoin($this->countryRepository, "country")
            ->On("country.short", "company.country")
            ->where()
            ->condition('country.long', Operation::NOT_IN, ['nopeTan', 'germany', 'Hellfiretanien'])
            ->end();
        $this->assertCount(0, $this->personRepository->findMatchingFilter($query));
    }
}