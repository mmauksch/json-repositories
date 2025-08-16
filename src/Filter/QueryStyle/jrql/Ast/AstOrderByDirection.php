<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast;

enum AstOrderByDirection: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';

    public static function fromString(string $direction): self
    {
        return self::from(mb_strtoupper($direction));
    }
}
