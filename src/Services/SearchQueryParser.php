<?php

namespace App\Services;

use App\Ast\AbstractNode;
use App\Ast\Node;
use App\Query\Clause;
use Doctrine\Common\Lexer\AbstractLexer;

/**
 * Class SearchQueryParser.
 *
 * This one parses query string of the following forms:
 * ("FIELD_IDENTIFIER[OPERATOR_IDENTIFIER]:VALUE")
 * ("FIELD_IDENTIFIER[OPERATOR_IDENTIFIER]:VALUE" AND|OR "FIELD_IDENTIFIER[OPERATOR_IDENTIFIER]:VALUE")
 * ("FIELD_IDENTIFIER[OPERATOR_IDENTIFIER]:VALUE" AND|OR "FIELD_IDENTIFIER[OPERATOR_IDENTIFIER]:VALUE") AND|OR (...)
 *
 * The contents wrapped in brackets is referred to as an "expression".
 * The contents wrapped in quotes is referred to as a "clause".
 */
class SearchQueryParser
{
    /**
     * Lexing instance.
     *
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
     * Parses an input string into an abstract syntax tree (AST) structure.
     *
     * @param string $input
     *   Query string.
     *
     * @return \App\Ast\Node
     *   Tree structure.
     */
    public function parse($input)
    {
        $this->lexer->setInput($input);
        $this->lexer->moveNext();

        return $this->buildAst();
    }

    /**
     * Builds the abstract syntax tree.
     *
     * Parses the contents of one or more expressions.
     *
     * @return \App\Ast\Node
     *   Syntax tree root node.
     */
    private function buildAst()
    {
        $operator = null;
        $childNodes[] = $this->buildExpression();

        // We simply keep building expressions if we encounter an operator at the end.
        while ($this->lexer->lookahead && $this->lexer->isNextToken(SearchQueryLexer::IDENTIFIER) && $_operator = $this->buildOperator()) {
            if (!$operator) {
                $operator = $_operator;
            }

            $childNodes[] = $this->buildExpression();
        }

        return new Node($operator, $childNodes);
    }

    /**
     * Converts a certain expression into a abstract syntax tree.
     *
     * Parses the contents of one or more clauses.
     *
     * @return \App\Ast\Node
     */
    private function buildExpression()
    {
        $this->tokenMatches(SearchQueryLexer::EXPRESSION_START);

        $operator = null;
        $childNodes[] = $this->buildClause();

        // We simply keep building clauses if we encounter an operator at the end.
        while ($this->lexer->lookahead && $this->lexer->isNextToken(SearchQueryLexer::IDENTIFIER) && $_operator = $this->buildOperator()) {
            if (!$operator) {
                $operator = $_operator;
            }
            $childNodes[] = $this->buildClause();
        }

        $this->tokenMatches(SearchQueryLexer::EXPRESSION_END);

        return new Node($operator, $childNodes);
    }

    /**
     * Builds a single clause instance.
     *
     * @return \App\Query\Clause
     *   Clause instance.
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
     * Reads the comparison operator.
     *
     * @return string
     *   Comparison operator.
     */
    private function buildOperator()
    {
        $this->tokenMatches(SearchQueryLexer::IDENTIFIER);

        $token = $this->lexer->token;
        $operatorValue = trim(strtolower($token['value']));
        if (!in_array($operatorValue, [AbstractNode::OPERATOR_OR, AbstractNode::OPERATOR_AND])) {
            $position = isset($token['position']) ? $token['position'] : -1;
            $error = 'Expected a valid operator, found "' . $operatorValue . '"';
            $error .= ' at position "' . $position . '".';

            throw new \RuntimeException($error);
        }

        return $operatorValue;
    }

    /**
     * Checks whether next token matches the type and moves the lexer pointer ahead.
     *
     * @param string $tokenType
     *   Token type to check against.
     *
     * @see \App\Services\SearchQueryLexer
     */
    private function tokenMatches($tokenType)
    {
        $aheadToken = $this->lexer->lookahead;
        $position = isset($aheadToken['position']) ? $aheadToken['position'] : -1;

        if (!$aheadToken || $aheadToken['type'] !== $tokenType) {
            $error = 'Expected a valid token of type "' . $this->lexer->getLiteral($tokenType) . '"';
            if (!$aheadToken) {
                $error .= ', none found';
            }
            else {
                $error .= ', found "' . $aheadToken['value'] . '"';
            }
            $error .= ' at position "' . $position . '".';

            throw new \RuntimeException($error);
        }

        $this->lexer->moveNext();
    }
}
