<?php

namespace Mmauksch\JsonRepositories\Tests\TestObjects;

class CompanyObject
{
    private string $name;
    private string $address;
    private string $city;
    private string $country;

    private ?string $boss;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): CompanyObject
    {
        $this->name = $name;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): CompanyObject
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): CompanyObject
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): CompanyObject
    {
        $this->country = $country;
        return $this;
    }

    public function getBoss(): ?string
    {
        return $this->boss;
    }

    public function setBoss(?string $boss): CompanyObject
    {
        $this->boss = $boss;
        return $this;
    }


}