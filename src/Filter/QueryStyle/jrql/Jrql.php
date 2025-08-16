<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql;

use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\ConditionGroupType;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\ExpressionGroupInterface;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstAndGroup;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstExpression;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstInnerJoin;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOperatorExpression;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOrderBy;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOrGroup;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstRoot;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstWhere;
use Mmauksch\JsonRepositories\Filter\QueryStyle\QueryBuilder;
use Mmauksch\JsonRepositories\Repository\AbstractJsonRepository;

class Jrql
{
    private Tokenizer $tokenizer;
    private Parser $parser;

    /** @var ?array<string, AbstractJsonRepository> */
    private ?array $neededRepositories;

    public function __construct(?array $neededRespositories = [])
    {
        $this->tokenizer = new Tokenizer();
        $this->parser = new Parser();
        $this->neededRepositories = $neededRespositories;
    }

    /**
     * parse a JRQL (Json-Repository-Query-Language) query and return a QueryBuilder
     */
    public function query(string $query): QueryBuilder
    {
        $tokens = $this->tokenizer->tokenize($query);
        $ast = $this->parser->parse($tokens);

        $queryBuilder = new QueryBuilder();
        return $this->buildQuery($queryBuilder, $ast);
    }

    private function buildQuery(QueryBuilder $queryBuilder, AstRoot $ast): QueryBuilder
    {
        $whereExpression = $ast->getWhere();
        foreach ($ast->getInnerJoins() as $joinStatement) {
            $this->buildInnerJoinClause($queryBuilder, $joinStatement);
        }

        if ($whereExpression !== null) {
            $this->buildWhereClause($queryBuilder, $whereExpression);
        }

        if ($ast->getOrderBy() !== null) {
            $this->buildOrderByClause($queryBuilder, $ast->getOrderBy());
        }

        if ($ast->getLimit() !== null) {
            $queryBuilder->limit($ast->getLimit()->getValue());
        }

        if ($ast->getOffset() !== null) {
            $queryBuilder->offset($ast->getOffset()->getValue());
        }

        return $queryBuilder;
    }

    private function buildInnerJoinClause(QueryBuilder $queryBuilder, AstInnerJoin $joinStatement): void
    {
        $repositoryName = $joinStatement->getRepository();
        $leftField = $joinStatement->getLeftField();
        $rightField = $joinStatement->getRightField();

        if (!isset($this->neededRepositories[$repositoryName])) {
            throw new \Exception("Repository '$repositoryName' ist nicht verfügbar. Verfügbare Repositories: " .
                implode(', ', array_keys($this->neededRepositories)));
        }

        $repository = $this->neededRepositories[$repositoryName];
        $joinBuilder = $queryBuilder->innerJoin($repository, $repositoryName);
        $joinBuilder->On($leftField, $rightField);
    }

    private function buildWhereClause(QueryBuilder $queryBuilder, AstWhere $whereExpression): void
    {
        $rootType = $this->determineRootConditionGroupType($whereExpression->getExpression());
        $whereBuilder = $queryBuilder->where($rootType);
        $this->buildSingleCondition($whereBuilder, $whereExpression->getExpression());
//        $whereBuilder->end();
    }

    private function determineRootConditionGroupType(AstExpression $expression): ConditionGroupType
    {
        if ($expression instanceof AstOrGroup) {
            return ConditionGroupType::OR;
        } elseif ($expression instanceof AstAndGroup) {
            return ConditionGroupType::AND;
        } else {
            return ConditionGroupType::AND;
        }
    }

    private function buildSingleCondition(ExpressionGroupInterface $builder, AstExpression $expression): void
    {
        if ($expression instanceof AstOperatorExpression) {
            $builder->condition(
                $expression->getField(),
                $expression->getOperator()->value,
                $expression->getValue()
            );
        } elseif ($expression instanceof AstAndGroup){
            $groupBuilder = $builder->andX();
            foreach ($expression->getExpressions() as $subExpression) {
                $this->buildSingleCondition($groupBuilder, $subExpression);
            }
//            $groupBuilder->endX();
        } elseif ($expression instanceof AstOrGroup){
            $groupBuilder = $builder->orX();
            foreach ($expression->getExpressions() as $subExpression) {
                $this->buildSingleCondition($groupBuilder, $subExpression);
            }
//            $groupBuilder->endX();
        }
    }

    private function buildOrderByClause(QueryBuilder $queryBuilder, AstOrderBy $orderByStatement): void
    {
        foreach ($orderByStatement->getFields() as $field) {
            $queryBuilder->orderBy($field->getField(), $field->getOrder()->value);
        }
    }
}