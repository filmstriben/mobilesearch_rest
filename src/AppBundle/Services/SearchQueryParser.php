<?php

namespace AppBundle\Services;

use AppBundle\Ast\Node;
use AppBundle\Query\Clause;
use Doctrine\Common\Lexer\AbstractLexer;

/**
 * Class SearchQueryParser.
 */
class SearchQueryParser
{
    /**
     * @var \Doctrine\Common\Lexer\AbstractLexer
     */
    private $lexer;

    /**
     * SearchQueryParser constructor.
     *
     * @param \Doctrine\Common\Lexer\AbstractLexer $lexer
     */
    public function __construct(AbstractLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * @param $input
     *
     * @return \AppBundle\Ast\Node
     */
    public function parse($input)
    {
        $this->lexer->setInput($input);
        $this->lexer->moveNext();

        return $this->buildAst();
    }

    /**
     * @return \AppBundle\Ast\Node
     */
    private function buildAst()
    {
        $operator = null;
        $childNodes[] = $this->buildExpression();

        while ($this->lexer->isNextToken(SearchQueryLexer::OPERATOR)) {
            $_operator = $this->buildOperator();
            if (!$operator) {
                $operator = $_operator;
            }

            $childNodes[] = $this->buildExpression();
        }

        if ($this->lexer->lookahead) {
            $this->tokenMatches(SearchQueryLexer::OPERATOR);
        }

        return new Node($operator, $childNodes);
    }

    /**
     * @return \AppBundle\Ast\Node
     */
    private function buildExpression()
    {
        $this->tokenMatches(SearchQueryLexer::EXPRESSION_START);

        $operator = null;
        $childNodes[] = $this->buildClause();

        while ($this->lexer->isNextToken(SearchQueryLexer::OPERATOR)) {
            $_operator = $this->buildOperator();
            if (!$operator) {
                $operator = $_operator;
            }
            $childNodes[] = $this->buildClause();
        }

        $this->tokenMatches(SearchQueryLexer::EXPRESSION_END);

        return new Node($operator, $childNodes);
    }

    /**
     * @return \AppBundle\Query\Clause
     */
    private function buildClause()
    {
        $this->tokenMatches(SearchQueryLexer::QUOTE);

        $this->tokenMatches(SearchQueryLexer::IDENTIFIER);
        $field = $this->lexer->token['value'];

        $this->tokenMatches(SearchQueryLexer::COMPARE);
        $operator = trim($this->lexer->token['value'], '[]');

        $this->tokenMatches(SearchQueryLexer::EQUALS);

        $this->tokenMatches(SearchQueryLexer::IDENTIFIER);
        $value = $this->lexer->token['value'];

        $this->tokenMatches(SearchQueryLexer::QUOTE);

        return new Clause($field, $operator, $value);
    }

    /**
     * @return string
     */
    private function buildOperator()
    {
        $this->tokenMatches(SearchQueryLexer::OPERATOR);

        return trim(strtolower($this->lexer->token['value']));
    }

    /**
     * @param $tokenType
     */
    private function tokenMatches($tokenType)
    {
        $aheadToken = $this->lexer->lookahead;
        $position = isset($aheadToken['position']) ? $aheadToken['position'] : -1;
        if (!$aheadToken || $aheadToken['type'] !== $tokenType) {
            $error = 'Expected a valid token of type "' . $this->lexer->getLiteral($tokenType) . '"';
            $error .= ' at position "' . $position . '"';
            throw new \RuntimeException($error);
        }

        $this->lexer->moveNext();
    }
}
