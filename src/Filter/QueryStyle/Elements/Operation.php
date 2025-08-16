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
    case IN = 'IN';
    case NOT_IN = 'NOT IN';

    static function fromString(string $operation): self
    {
        return self::from(mb_strtoupper($operation));
    }

}