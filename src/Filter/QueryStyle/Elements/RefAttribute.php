<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;


class RefAttribute
{

    const DELIMITER = ".";
    private ?string $ref;
    private string $attribute;

    public function __construct(?string $ref, string $attribute) {
        $this->ref = $ref;
        $this->attribute = $attribute;
    }

    public static function isForeignRef(string $checkIfRef): bool
    {
        return str_contains($checkIfRef, self::DELIMITER);
    }

    public static function fromString($string): RefAttribute
    {
        if(!self::isForeignRef($string)) {
            return new RefAttribute(null, $string);
        }

        return new RefAttribute(
            substr($string, 0, strpos($string, self::DELIMITER)),
            substr($string, strpos($string, self::DELIMITER) + 1));
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function __toString(): string
    {
        return $this->ref . self::DELIMITER . $this->attribute;
    }



}