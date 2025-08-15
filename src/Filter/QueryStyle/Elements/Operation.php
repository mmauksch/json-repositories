<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

enum Operation: string
{
    case EQ = '=';
    case NEQ = '!=';
    case GT = '>';
    case GTE = '>=';
    case LT = '<';
    case LTE = '<=';

    static function fromString(string $operation): self
    {
        return self::from($operation);
    }

}