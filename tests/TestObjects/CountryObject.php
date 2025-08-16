<?php

namespace Mmauksch\JsonRepositories\Tests\TestObjects;

class CountryObject
{
    private string $short;
    private string $long;

    private string $overlord;

    private string $description;

    public function getShort(): string
    {
        return $this->short;
    }

    public function setShort(string $short): CountryObject
    {
        $this->short = $short;
        return $this;
    }

    public function getLong(): string
    {
        return $this->long;
    }

    public function setLong(string $long): CountryObject
    {
        $this->long = $long;
        return $this;
    }

    public function getOverlord(): string
    {
        return $this->overlord;
    }

    public function setOverlord(string $overlord): CountryObject
    {
        $this->overlord = $overlord;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): CountryObject
    {
        $this->description = $description;
        return $this;
    }



}