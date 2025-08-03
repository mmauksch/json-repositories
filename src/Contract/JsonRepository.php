<?php

namespace Mmauksch\JsonRepositories\Contract;

use Mmauksch\JsonRepositories\Contract\Extensions\FilterableJsonRepository;

/**
 * @template T of object
 * @implements BasicJsonRepository<T>
 * @implements FilterableJsonRepository<T>
 */
interface JsonRepository extends BasicJsonRepository, FilterableJsonRepository
{
}