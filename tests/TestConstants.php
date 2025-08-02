<?php

namespace Mmauksch\JsonRepositories\Tests;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class TestConstants
{
    public static function JsonSerializer() : SerializerInterface
    {
        return new Serializer(
            [new ObjectNormalizer(
                null, null, null,
                new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()])
            ), new ArrayDenormalizer()],
            [new JsonEncoder()]
        );
    }
}