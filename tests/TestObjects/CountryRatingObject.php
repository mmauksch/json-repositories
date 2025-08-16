<?php

namespace Mmauksch\JsonRepositories\Tests\TestObjects;

class CountryRatingObject
{
    private string $id;
    private string $rating;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): CountryRatingObject
    {
        $this->id = $id;
        return $this;
    }

    public function getRating(): string
    {
        return $this->rating;
    }

    public function setRating(string $rating): CountryRatingObject
    {
        $this->rating = $rating;
        return $this;
    }


}