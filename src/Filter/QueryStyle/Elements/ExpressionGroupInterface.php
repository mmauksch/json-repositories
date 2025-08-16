<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

interface ExpressionGroupInterface
{
    public function condition(string $attribute, Operation|string $operation, mixed $value): ExpressionGroupInterface;

    public function andX(): ExpressionGroupInterface;

    public function orX(): ExpressionGroupInterface;

}