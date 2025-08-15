<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

class SortingStep
{
    private string $attribute;
    private SortOrder $order;


    public function __construct(string $attribute, SortOrder $order) {
        $this->attribute = $attribute;
        $this->order = $order;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getOrder(): SortOrder
    {
        return $this->order;
    }




}