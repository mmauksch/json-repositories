<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Mmauksch\JsonRepositories\Contract\Extensions\FastFilter;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Tests\TestObjects\ComplexObject;

/**
 * @template T of ComplexObject
 * @implements Filter<ComplexObject>
 */
class ComplexFilter implements FastFilter
{
    private string $company;

    public function __construct(string $company) {
        $this->company = $company;
    }
    public function __invoke(object $object): bool
    {
        return $object->getCompany() === $this->company;
    }

    public function useIndexes(): array
    {
        return ['company' => $this->company];
    }
}