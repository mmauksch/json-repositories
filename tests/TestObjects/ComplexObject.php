<?php

namespace Mmauksch\JsonRepositories\Tests\TestObjects;

class ComplexObject extends SimpleObject
{
    private string $description;
    private int $age;
    private bool $active;
    private string $company;

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): complexObject
    {
        $this->description = $description;
        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): complexObject
    {
        $this->age = $age;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): complexObject
    {
        $this->active = $active;
        return $this;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function setCompany(string $company): complexObject
    {
        $this->company = $company;
        return $this;
    }


}