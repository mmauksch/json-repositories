<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Jrql;

class JqlQueryTest extends RepositoryQueryTestCase
{
    public function testSimpleInnerJoinJrql()
    {
        $query = (new Jrql(["com" => $this->companyRepository]))->query("
            INNER JOIN com ON com.name = company
            WHERE com.city = 'a-city'
        ");
        $result = $this->personRepository->findMatchingFilter($query);
        $this->assertCount(3, $result);
    }

    public function testSimpleJoinWithMultipleConditionsJrql()
    {
        $query = (new Jrql(["com" => $this->companyRepository]))->query("
            INNER JOIN com ON com.name = company
            WHERE com.city = 'a-city' AND age < 20
        ");
        $this->assertCount(1, $this->personRepository->findMatchingFilter($query));
    }

    public function testSimpleJoinWithConditionsNoResultsOnFalseConditionsJrql() {
        $query = (new Jrql(["com" => $this->companyRepository]))->query("
            INNER JOIN com ON com.name = company
            WHERE com.city = 'a-city' AND com.address = 'voelligFalsch' AND age < 20
        ");
        $this->assertCount(0, $this->personRepository->findMatchingFilter($query));
    }

    public function testNestedJoinWithConditionOnJoinedRepositoryJrql() {
        $query = (new Jrql(["country" => $this->countryRepository, "company" => $this->companyRepository]))->query("
            INNER JOIN country ON company.country = country.short 
            INNER JOIN company ON company.name = company 
            WHERE country.long = 'germany'
        ");
        $this->assertCount(3, $this->personRepository->findMatchingFilter($query));

        $query = (new Jrql(["country" => $this->countryRepository, "company" => $this->companyRepository]))->query("
            INNER JOIN country ON company.country = country.short 
            INNER JOIN company ON company.name = company 
            WHERE country.long = 'Hellfiretanien'
        ");
        $this->assertCount(1, $this->personRepository->findMatchingFilter($query));
    }

    public function testJoinWithReferenceInConditionJrql()
    {
        $query = (new Jrql(["company" => $this->companyRepository, "country" => $this->countryRepository]))->query("
            INNER JOIN company ON company = company.name
            INNER JOIN country ON country.short = company.country
            WHERE country.overlord = company.boss
        ");
        $this->assertCount(3, $this->personRepository->findMatchingFilter($query));
    }
}