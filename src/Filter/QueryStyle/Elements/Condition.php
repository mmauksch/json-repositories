<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

class Condition
{
    use GetPropertyValueTrait;
    public string $attribute;
    public Operation $operation;
    public mixed $value;

    public function __construct(string $attribute, Operation|string $operation, mixed $value) {
        $this->attribute = $attribute;
        $this->operation = $operation instanceof Operation? $operation : Operation::fromString($operation);
        $this->value = $value;
    }

    public function evaluate(object $data): bool
    {
        $actual = $this->getValue($data, $this->attribute);

        return match ($this->operation) {
            Operation::EQ  => $actual == $this->value,
            Operation::NEQ => $actual != $this->value,
            Operation::GT  => $actual > $this->value,
            Operation::GTE => $actual >= $this->value,
            Operation::LT  => $actual < $this->value,
            Operation::LTE => $actual <= $this->value,
        };
    }
}
