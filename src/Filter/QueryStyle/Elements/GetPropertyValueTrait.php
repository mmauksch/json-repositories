<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

trait GetPropertyValueTrait
{
    private function getValue(array|object $data, string $attr): mixed
    {
        if (is_array($data)) {
            return $data[$attr] ?? null;
        }

        $publicProps = get_object_vars($data); // nur public, kein Fatal
        if (array_key_exists($attr, $publicProps)) {
            return $publicProps[$attr];
        }

        $uc = ucfirst($attr);
        foreach (["get{$uc}", "is{$uc}", "has{$uc}"] as $m) {
            if (is_callable([$data, $m])) {
                return $data->{$m}();
            }
        }

        $ref = new \ReflectionClass($data);
        if ($ref->hasProperty($attr)) {
            $prop = $ref->getProperty($attr);
            return $prop->getValue($data);
        }
        if (method_exists($data, '__get')) {
            return $data->{$attr};
        }

        return null;
    }
//
//    private function getValueWithJoins(array|object $data, string $attr, array $joinResults): mixed
//    {
//        if (str_contains($attr, '.')) {
//            [$prefix, $subAttr] = explode('.', $attr, 2);
//
//            if (isset($joinResults[$prefix])) {
//                foreach ($joinResults[$prefix] as $joined) {
//                    $val = $this->getValue($joined, $subAttr);
//                    if ($val !== null) {
//                        return $val;
//                    }
//                }
//                return null;
//            }
//        }
//
//        return $this->getValue($data, $attr);
//    }

    private function getValueWithJoins(array|object $data, string $attr, array $joinResults): mixed
    {
        if (str_contains($attr, '.')) {
            [$prefix, $subAttr] = explode('.', $attr, 2);

            if (isset($joinResults[$prefix])) {
                $joined = $joinResults[$prefix];
                return $this->getValue($joined, $subAttr);
            }
        }

        return $this->getValue($data, $attr);
    }
}