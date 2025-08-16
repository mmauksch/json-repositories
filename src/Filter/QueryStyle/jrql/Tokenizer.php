<?php

namespace Mmauksch\JsonRepositories\Filter\QueryStyle\jrql;

class Tokenizer
{
    private string $input;
    private int $position;
    private int $length;

    /**
     * @return Token[]
     */
    public function tokenize(string $input): array
    {
        $this->input = trim($input);
        $this->position = 0;
        $this->length = strlen($this->input);

        $tokens = [];

        while ($this->position < $this->length) {
            $this->skipWhitespace();

            if ($this->position >= $this->length) {
                break;
            }

            $token = $this->nextToken();
            if ($token) {
                $tokens[] = $token;
            }
        }

        $tokens[] = new Token(Token::EOF, null, $this->position);
        return $tokens;
    }

    private function nextToken(): ?Token
    {
        $char = $this->currentChar();

        // special characters
        switch ($char) {
            case '(':
                $this->advance();
                return new Token(Token::LPAREN, '(', $this->position - 1);
            case ')':
                $this->advance();
                return new Token(Token::RPAREN, ')', $this->position - 1);
            case '[':
                $this->advance();
                return new Token(Token::LBRACKET, '[', $this->position - 1);
            case ']':
                $this->advance();
                return new Token(Token::RBRACKET, ']', $this->position - 1);
            case ',':
                $this->advance();
                return new Token(Token::COMMA, ',', $this->position - 1);
            case '\'':
            case '"':
                return $this->readString();
        }

        // multi-character operators
        if ($char === '!' && $this->peek() === '=') {
            $this->advance(2);
            return new Token(Token::OPERATOR, '!=', $this->position - 2);
        }
        if ($char === '>' && $this->peek() === '=') {
            $this->advance(2);
            return new Token(Token::OPERATOR, '>=', $this->position - 2);
        }
        if ($char === '<' && $this->peek() === '=') {
            $this->advance(2);
            return new Token(Token::OPERATOR, '<=', $this->position - 2);
        }

        // single-character operators
        if (in_array($char, ['=', '>', '<'])) {
            $this->advance();
            return new Token(Token::OPERATOR, $char, $this->position - 1);
        }

        // numbers
        if (is_numeric($char)) {
            return $this->readNumber();
        }

        // identifiers
        if (ctype_alpha($char) || $char === '_') {
            return $this->readIdentifier();
        }

        throw new \Exception("Unexpectet character: '$char' at position {$this->position}");
    }

    private function readString(): Token
    {
        $quote = $this->currentChar();
        $start = $this->position;
        $this->advance(); // skip opening quote

        $value = '';
        while ($this->position < $this->length && $this->currentChar() !== $quote) {
            if ($this->currentChar() === '\\') {
                $this->advance();
                if ($this->position < $this->length) {
                    $escaped = $this->currentChar();
                    switch ($escaped) {
                        case 'n':
                            $value .= "\n";
                            break;
                        case 't':
                            $value .= "\t";
                            break;
                        case 'r':
                            $value .= "\r";
                            break;
                        case '\\':
                            $value .= "\\";
                            break;
                        case '"':
                        case "'":
                            $value .= $escaped;
                            break;
                        default:
                            $value .= $escaped;
                            break;
                    }
                    $this->advance();
                }
            } else {
                $value .= $this->currentChar();
                $this->advance();
            }
        }

        if ($this->position >= $this->length) {
            throw new \Exception("Unterminated string at position $start");
        }

        $this->advance(); // skip closing quote
        return new Token(Token::STRING, $value, $start);
    }

    private function readNumber(): Token
    {
        $start = $this->position;
        $value = '';

        while ($this->position < $this->length &&
            (is_numeric($this->currentChar()) || $this->currentChar() === '.')) {
            $value .= $this->currentChar();
            $this->advance();
        }

        $numericValue = str_contains($value, '.') ? (float) $value : (int) $value;
        return new Token(Token::NUMBER, $numericValue, $start);
    }

    private function readIdentifier(): Token
    {
        $start = $this->position;
        $value = '';

        while ($this->position < $this->length &&
            (ctype_alnum($this->currentChar()) || $this->currentChar() === '_' || $this->currentChar() === '.')) {
            $value .= $this->currentChar();
            $this->advance();
        }

        $keywords = [
            'WHERE' => Token::WHERE,
            'ORDER' => Token::ORDER,
            'BY' => Token::BY,
            'LIMIT' => Token::LIMIT,
            'OFFSET' => Token::OFFSET,
            'AND' => Token::AND,
            'OR' => Token::OR,
            'ASC' => Token::ASC,
            'DESC' => Token::DESC,
            'IN' => Token::IN,
            'NOT' => Token::NOT_IN,
            'true' => Token::BOOLEAN,
            'false' => Token::BOOLEAN,
            'null' => Token::NULL,
            'INNER' => Token::INNER,
            'JOIN' => Token::JOIN,
            'ON' => Token::ON,
        ];

        $upperValue = strtoupper($value);

        if (isset($keywords[$upperValue])) {
            $tokenType = $keywords[$upperValue];
            $tokenValue = match($tokenType) {
                Token::BOOLEAN => $value === 'true',
                Token::NULL => null,
                default => $value
            };
            return new Token($tokenType, $tokenValue, $start);
        }

        return new Token(Token::IDENTIFIER, $value, $start);
    }

    private function skipWhitespace(): void
    {
        while ($this->position < $this->length && ctype_space($this->currentChar())) {
            $this->advance();
        }
    }

    private function currentChar(): string
    {
        return $this->position < $this->length ? $this->input[$this->position] : '';
    }

    private function peek(int $offset = 1): string
    {
        $pos = $this->position + $offset;
        return $pos < $this->length ? $this->input[$pos] : '';
    }

    private function advance(int $count = 1): void
    {
        $this->position += $count;
    }
}
