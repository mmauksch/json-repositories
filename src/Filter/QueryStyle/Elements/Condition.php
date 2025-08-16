<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

use function PHPUnit\Framework\stringContains;

class Condition
{
    use GetPropertyValueTrait;
    public string $attribute;
    public Operation $operation;
    public mixed $value;

    /**
     * @throws \Exception
     */
    public function __construct(string $attribute, Operation|string $operation, mixed $value) {
        $this->attribute = $attribute;
        $this->operation = $operation instanceof Operation? $operation : Operation::fromString($operation);
        $this->value = $value;
        if(($this->operation == Operation::IN || $this->operation == Operation::NOT_IN) && !is_array($value)){
            throw new \Exception("{$this->operation->value} condition value must be an array");
        }
    }

    /**
     * @param object $data
     * @param array<string, array|iterable> $joinSet
     * @return bool
     */
    public function evaluate(object $data, array $joinSet): bool
    {
        $actual = $this->getValueWithJoins($data, $this->attribute, $joinSet);

        if($this->operation == Operation::IN ){
            foreach ($this->value as $v){
                if($v == $actual){
                    return true;
                }
            }
            return false;
        }elseif($this->operation == Operation::NOT_IN){
            foreach ($this->value as $v){
                if($v == $actual){
                    return false;
                }
            }
            return true;
        }

        $value = $this->value;
        if ($value instanceof RefAttribute) {
            $value = $this->getValueWithJoins($data, $value, $joinSet);
        }

        return match ($this->operation) {
            Operation::EQ  => $actual ==  $value,
            Operation::NEQ => $actual !=  $value,
            Operation::GT  => $actual >   $value,
            Operation::GTE => $actual >=  $value,
            Operation::LT  => $actual <   $value,
            Operation::LTE => $actual <=  $value,
        };
    }

//    /**
//     * @param object $data
//     * @param array<string, array|iterable> $joinSet
//     * @return bool
//     */
//    private function evaluateLeftOwnRefs(object $data, array $joinSet)
//    {
//
//    }
//    /**
//     * @param object $data
//     * @param array $joinSet
//     * @return bool
//     */
//    private function evaluateLeftForeignRefs(object $data, array $joinSet)
//    {
//
//    }


}
