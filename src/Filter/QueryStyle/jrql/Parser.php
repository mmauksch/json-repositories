<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql;

use Exception;
use Mmauksch\JsonRepositories\Filter\QueryStyle\Elements\RefAttribute;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstAndGroup;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstExpression;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOperatorExpression;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstInnerJoin;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstLimit;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstNode;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOffset;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOperatior;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOrderBy;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOrderByField;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOrGroup;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstRoot;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstWhere;
use Mmauksch\JsonRepositories\Filter\QueryStyle\jrql\Ast\AstOrderByDirection;

class Parser
{
    private array $tokens;
    private int $position;
    private int $length;

    /**
     * @param Token[] $tokens
     * @return AstRoot
     * @throws Exception
     */
    public function parse(array $tokens): AstRoot
    {
        $this->tokens = $tokens;
        $this->position = 0;
        $this->length = count($tokens);

        $ast = new AstRoot();

        while (!$this->isAtEnd()) {
            $statement = $this->parseStatement();
            if ($statement) {
                if ($statement instanceof AstWhere)
                    $ast->setWhere($statement);
                elseif ($statement instanceof AstOrderBy)
                    $ast->setOrderBy($statement);
                elseif ($statement instanceof AstLimit)
                    $ast->setLimit($statement);
                elseif ($statement instanceof AstOffset)
                    $ast->setOffset($statement);
                elseif ($statement instanceof AstInnerJoin)
                    $ast->addInnerJoin($statement);
            }
        }

        return $ast;
    }

    /**
     * @throws Exception
     */
    private function parseStatement(): ?AstNode
    {
        $token = $this->currentToken();

        switch ($token->type) {
            case Token::INNER:
                return $this->parseInnerJoinClause();
            case Token::WHERE:
                return $this->parseWhereClause();
            case Token::ORDER:
                return $this->parseOrderByClause();
            case Token::LIMIT:
                return $this->parseLimitClause();
            case Token::OFFSET:
                return $this->parseOffsetClause();
            default:
                // try to parse a non-explicit where clause
                if ($this->isConditionStart()) {
                    return new AstWhere($this->parseOrExpression());
                }
                $this->advance();
                return null;
        }
    }

    /**
     * @throws Exception
     */
    private function parseInnerJoinClause(): AstInnerJoin
    {
        $this->consume(Token::INNER);
        $this->consume(Token::JOIN);

        $repositoryName = $this->consume(Token::IDENTIFIER)->value;
        $this->consume(Token::ON);

        $leftField = $this->consume(Token::IDENTIFIER)->value;
        $this->consume(Token::OPERATOR, '=');
        $rightField = $this->consume(Token::IDENTIFIER)->value;

        return new AstInnerJoin($repositoryName, $leftField, $rightField);
    }

    /**
     * @throws Exception
     */
    private function parseWhereClause(): AstWhere
    {
        $this->consume(Token::WHERE);
        $conditions = $this->parseOrExpression();

        return new AstWhere($conditions);
    }

    /**
     * @throws Exception
     */
    private function parseOrExpression(): AstExpression
    {
        $left = $this->parseAndExpression();

        $orGroups = [$left];

        while ($this->match(Token::OR)) {
            $right = $this->parseAndExpression();
            $orGroups[] = $right;
        }

        // only one OR-Gruppe, return it directly
        if (count($orGroups) === 1) {
            return $orGroups[0];
        }

        // multiple OR-Gruppen: create OR_GROUP
        return new AstOrGroup($orGroups);
    }

    /**
     * Pparse AND expression (higher precedence than OR)
     * @return AstExpression
     * @throws Exception
     */
    private function parseAndExpression(): AstExpression
    {
        $left = $this->parsePrimaryExpression();

        $andConditions = [$left];

        while ($this->match(Token::AND)) {
            $right = $this->parsePrimaryExpression();
            $andConditions[] = $right;
        }

        // only one condition, return it directly
        if (count($andConditions) === 1) {
            return $andConditions[0];
        }

        // multiple AND conditions: create AND_GROUP
        return new AstAndGroup($andConditions);
    }

    /**
     * condition or (conditions)
     * @return AstExpression
     * @throws Exception
     */
    private function parsePrimaryExpression(): AstExpression
    {
        if ($this->match(Token::LPAREN)) {
            $expression = $this->parseOrExpression();
            $this->consume(Token::RPAREN);
            return $expression;
        }

        return $this->parseCondition();
    }

    /**
     * @throws Exception
     */
    private function parseCondition(): AstOperatorExpression
    {
        $field = $this->consume(Token::IDENTIFIER)->value;
        $operator = $this->parseOperator();
        $value = $this->parseValue();

        return new AstOperatorExpression($field, AstOperatior::fromString($operator), $value);
    }

    /**
     * @throws Exception
     */
    private function parseOperator(): string
    {
        $token = $this->currentToken();

        switch ($token->type) {
            case Token::OPERATOR:
                $this->advance();
                return $token->value;
            case Token::IN:
                $this->advance();
                return 'IN';
            case Token::NOT_IN:
                $this->advance();
                if ($this->match(Token::IN)) {
                    return 'NOT IN';
                }
                throw new Exception("Expected 'IN' after 'NOT' at position {$token->position}");
            default:
                throw new Exception("Expected operator at position {$token->position}");
        }
    }

    /**
     * @throws Exception
     */
    private function parseValue(): mixed
    {
        $token = $this->currentToken();

        switch ($token->type) {
            case Token::STRING:
            case Token::NUMBER:
            case Token::BOOLEAN:
            case Token::NULL:
                $this->advance();
                return $token->value;
            case Token::LBRACKET:
                return $this->parseArray();
            case Token::IDENTIFIER:
                // Könnte eine Referenz auf ein anderes Feld sein
                $this->advance();
                return RefAttribute::fromString($token->value);
            default:
                throw new Exception("Expected value at position {$token->position}");
        }
    }

    /**
     * @throws Exception
     */
    private function parseArray(): array
    {
        $this->consume(Token::LBRACKET);
        $values = [];

        if (!$this->check(Token::RBRACKET)) {
            do {
                $values[] = $this->parseValue();
            } while ($this->match(Token::COMMA));
        }

        $this->consume(Token::RBRACKET);
        return $values;
    }

    /**
     * @throws Exception
     */
    private function parseOrderByClause(): AstOrderBy
    {
        if ($this->check(Token::ORDER)) {
            $this->advance();
            if ($this->check(Token::BY)) {
                $this->advance(); // BY überspringen
            }else{
                throw new Exception("Expected 'BY' after 'ORDER' at position {$this->currentToken()->position}");
            }
        }

        $fields = [];
        do {
            // parse order field
            $fieldToken = $this->currentToken();
            $field = null;

            if ($this->check(Token::IDENTIFIER)) {
                $field = $this->advance()->value;
            } elseif ($this->check(Token::ASC) || $this->check(Token::DESC)) {
                // allow asc/desc as a field name before comma, direction or end of statement
                $nextToken = $this->peekNext();
                if ($nextToken && ($nextToken->type === Token::COMMA ||
                        $nextToken->type === Token::ASC ||
                        $nextToken->type === Token::DESC ||
                        $this->isEndOfOrderByStatement($nextToken))) {
                    $field = $this->advance()->value;
                } else {
                    throw new Exception("Expected field identifier before ORDER BY direction at position {$fieldToken->position}");
                }
            } else {
                throw new Exception("Expected field identifier in ORDER BY at position {$fieldToken->position}");
            }

            if ($this->match(Token::ASC)) {
                $direction = AstOrderByDirection::ASC;
            } elseif ($this->match(Token::DESC)) {
                $direction = AstOrderByDirection::DESC;
            } elseif ($this->match(Token::COMMA)) {
                $direction = AstOrderByDirection::ASC;
            } elseif($this->isEndOfOrderByStatement($this->currentToken())){
                $direction = AstOrderByDirection::ASC;
            } else {
                throw new Exception("Expected ASC or DESC after field identifier in ORDER BY at position {$fieldToken->position}");
            }

            $fields[] = new AstOrderByField($field, $direction);

        } while ($this->match(Token::COMMA));

        return new AstOrderBy($fields);
    }

    /**
     * @throws Exception
     */
    private function parseLimitClause(): AstLimit
    {
        $this->consume(Token::LIMIT);
        $value = $this->consume(Token::NUMBER)->value;

        return new AstLimit($value);
    }

    /**
     * @throws Exception
     */
    private function parseOffsetClause(): AstOffset
    {
        $this->consume(Token::OFFSET);
        $value = $this->consume(Token::NUMBER)->value;

        return new AstOffset($value);
    }

    private function isConditionStart(): bool
    {
        return $this->check(Token::IDENTIFIER);
    }

    private function isEndOfOrderByStatement(Token $token): bool
    {
        return in_array($token->type, [Token::WHERE, Token::ORDER, Token::BY, Token::LIMIT, Token::OFFSET, Token::INNER, Token::EOF]);
    }

    private function currentToken(): Token
    {
        return $this->tokens[$this->position] ?? new Token(Token::EOF, null, 0);
    }

    private function peekNext(): ?Token
    {
        return $this->tokens[$this->position + 1] ?? null;
    }

    private function advance(): Token
    {
        if (!$this->isAtEnd()) {
            $this->position++;
        }
        return $this->tokens[$this->position - 1];
    }

    private function isAtEnd(): bool
    {
        return $this->position >= $this->length || $this->currentToken()->type === Token::EOF;
    }

    private function match(string $type): bool
    {
        if ($this->check($type)) {
            $this->advance();
            return true;
        }
        return false;
    }

    private function check(string $type): bool
    {
        if ($this->isAtEnd()) return false;
        return $this->currentToken()->type === $type;
    }

    /**
     * @throws Exception
     */
    private function consume(string $type, ?string $expectedValue = null): Token
    {
        if ($this->check($type)) {
            $token = $this->advance();
            if ($expectedValue !== null && $token->value !== $expectedValue) {
                throw new Exception("Expected '$expectedValue' but got '{$token->value}' at position {$token->position}");
            }
            return $token;
        }

        $current = $this->currentToken();
        throw new Exception("Expected $type but got {$current->type} at position {$current->position}");
    }
}