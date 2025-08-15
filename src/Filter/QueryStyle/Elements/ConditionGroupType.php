<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

enum ConditionGroupType: string
{
    case AND = 'AND';
    case OR = 'OR';

    static function fromString(string $type): self
    {
        return self::from(mb_strtoupper($type));
    }
}