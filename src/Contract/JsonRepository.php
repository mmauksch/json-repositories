<?php

namespace Mmauksch\JsonRepositories\Contract;

/**
 * @template T of object
 * @implements BasicJsonRepository<T>
 * @implements FilterAwareJsonRepository<T>
 */
interface JsonRepository extends BasicJsonRepository, FilterAwareJsonRepository
{
}