<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\Elements;

use Mmauksch\JsonRepositories\Filter\QueryStyle\QueryBuilder;
use Mmauksch\JsonRepositories\Repository\AbstractJsonRepository;

class InnerJoinBuilder
{
    const DELIMITER = ".";
    const TYPE_INTERNAL = "internal";
    const TYPE_EXTERNAL = "external";

    private QueryBuilder $queryBuilder;
    private AbstractJsonRepository $repository;
    private string $name_prefix;

    private ?RefAttribute $joinRepoAttribute;
    private ?RefAttribute $otherRefAttribute;
    private ?RefAttribute $noRefAttribute;

    private string $type;

    public function __construct(QueryBuilder $queryBuilder, AbstractJsonRepository $repository, $name_prefix)
    {
        $this->queryBuilder = $queryBuilder;
        $this->repository = $repository;
        $this->name_prefix = $name_prefix;
        $this->joinRepoAttribute = null;
        $this->noRefAttribute = null;
        $this->otherRefAttribute = null;
        $this->type = "";
    }

    public function On(string $left, string $right): QueryBuilder
    {
        if(str_starts_with($left, $this->name_prefix.self::DELIMITER)
            && !str_contains($right, self::DELIMITER)){
//            $this->joinRepoAttribute = new RefAttribute($this->name_prefix, substr($left, strlen($this->name_prefix) + 1));
//            $this->noRefAttribute = new RefAttribute(null,$right);
            $this->joinRepoAttribute = RefAttribute::fromString($left);
            $this->noRefAttribute = RefAttribute::fromString($right);

            $this->type = self::TYPE_INTERNAL;
            return $this->queryBuilder;
        } elseif (str_starts_with($right, $this->name_prefix.self::DELIMITER)
            && !str_contains($left, self::DELIMITER)){
//            $this->joinRepoAttribute = new RefAttribute(null, $left);
//            $this->noRefAttribute = new RefAttribute($this->name_prefix, substr($right, strlen($this->name_prefix) + 1));
            $this->joinRepoAttribute = RefAttribute::fromString($right);
            $this->noRefAttribute = RefAttribute::fromString($left);

            $this->type = self::TYPE_INTERNAL;
            return $this->queryBuilder;
        }elseif(str_starts_with($left, $this->name_prefix.self::DELIMITER)
            && str_contains($right, self::DELIMITER)){
//            $this->joinRepoAttribute = new RefAttribute($this->name_prefix, substr($left, strlen($this->name_prefix) + 1));
//            $this->otherRefAttribute = new RefAttribute(
//                substr($right, 0, strpos($right, self::DELIMITER)),
//                substr($right, strpos($right, self::DELIMITER) + 1));
            $this->joinRepoAttribute = RefAttribute::fromString( $left);
            $this->otherRefAttribute = RefAttribute::fromString($right);

            $this->type = self::TYPE_EXTERNAL;
            return $this->queryBuilder;
        } elseif (str_starts_with($right, $this->name_prefix.self::DELIMITER)
            && str_contains($left, self::DELIMITER)){
//            $this->joinRepoAttribute = new RefAttribute($this->name_prefix, substr($right, strlen($this->name_prefix) + 1));
//            $this->otherRefAttribute = new RefAttribute(
//                substr($left, 0, strpos($left, self::DELIMITER)),
//                substr($left, strpos($left, self::DELIMITER) + 1));

            $this->joinRepoAttribute = RefAttribute::fromString( $right);
            $this->otherRefAttribute = RefAttribute::fromString($left);
            $this->type = self::TYPE_EXTERNAL;
            return $this->queryBuilder;
        }

        throw new \LogicException("Join conditions broken");
    }

    /***
     * @internal This method should only be used by the QueryBuilder itself
     * @return string
     */
    public function __getJoinRepoAttribute(): RefAttribute
    {
        return $this->joinRepoAttribute;
    }

    /***
     * @internal This method should only be used by the QueryBuilder itself
     * @return string
     */
    public function __getNoRefAttribute(): RefAttribute
    {
        return $this->noRefAttribute;
    }

    /***
     * @internal This method should only be used by the QueryBuilder itself
     * @return string
     */
    public function __getOtherRefAttribute(): RefAttribute
    {
        return $this->otherRefAttribute;
    }


    public function __getRepository(): AbstractJsonRepository
    {
        return $this->repository;
    }

    public function __getType(): string
    {
        return $this->type;
    }

}