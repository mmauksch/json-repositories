<?php

namespace Mmauksch\JsonRepositories\Contract;

use Mmauksch\JsonRepositories\Contract\Extensions\FilterAwareJsonRepository;

/**
 * @template T of object
 * @implements BasicJsonRepository<T>
 * @implements FilterAwareJsonRepository<T>
 */
interface JsonRepository extends BasicJsonRepository, FilterAwareJsonRepository
{
}