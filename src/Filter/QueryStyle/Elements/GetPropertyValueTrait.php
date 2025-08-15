<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

trait GetPropertyValueTrait
{
    private function getValue(object $data, string $attr): mixed
    {
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
}