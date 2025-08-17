<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql;

class Token
{
    // jrql token
    public const WHERE = 'WHERE';
    public const ORDER = 'ORDER';
    public const BY = 'BY';
    public const LIMIT = 'LIMIT';
    public const OFFSET = 'OFFSET';
    public const AND = 'AND';
    public const OR = 'OR';
    public const ASC = 'ASC';
    public const DESC = 'DESC';
    public const IN = 'IN';
    public const NOT_IN = 'NOT_IN';
    public const BOOLEAN = 'BOOLEAN';
    public const NULL = 'NULL';
    public const INNER = 'INNER';
    public const JOIN = 'JOIN';
    public const ON = 'ON';

    // basis token
    public const IDENTIFIER = 'IDENTIFIER';
    public const STRING = 'STRING';
    public const NUMBER = 'NUMBER';
    public const OPERATOR = 'OPERATOR';
    public const LPAREN = 'LPAREN';
    public const RPAREN = 'RPAREN';
    public const LBRACKET = 'LBRACKET';
    public const RBRACKET = 'RBRACKET';
    public const COMMA = 'COMMA';
    public const EOF = 'EOF';

    public function __construct(
        public readonly string $type,
        public readonly mixed $value,
        public readonly int $position
    ) {}
}
