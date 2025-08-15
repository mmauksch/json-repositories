<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

enum SortOrder: string
{
    case DESC = 'DESC';
    case ASC = 'ASC';

    static function fromString(string $sortOrder): self
    {
        return self::from(mb_strtoupper($sortOrder));
    }

}