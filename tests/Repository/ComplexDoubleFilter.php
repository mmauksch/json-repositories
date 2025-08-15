<?php

namespace Mmauksch\JsonRepositories\Tests\Repository;

use Mmauksch\JsonRepositories\Contract\Extensions\FastFilter;
use Mmauksch\JsonRepositories\Contract\Extensions\Filter;
use Mmauksch\JsonRepositories\Tests\TestObjects\ComplexObject;

/**
 * @template T of ComplexObject
 * @implements Filter<ComplexObject>
 */
class ComplexDoubleFilter implements FastFilter
{
    private string $name;
    private string $company;

    public function __construct(string $name, string $company) {
        $this->name = $name;
        $this->company = $company;
    }
    public function __invoke(object $object): bool
    {
        return $object->getName() === $this->name && $object->getCompany() === $this->company;
    }

    public function useIndexes(): array
    {
        return ['name' => $this->name, 'company' => $this->company];
    }
}