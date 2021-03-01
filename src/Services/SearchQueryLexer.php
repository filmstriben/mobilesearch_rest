<?php

namespace App\Services;

use Doctrine\Common\Lexer\AbstractLexer;

/**
 * Class SearchQueryLexer.
 *
 * This one parses a query string into a set of lexical values.
 */
class SearchQueryLexer extends AbstractLexer
{
    const EXPRESSION_START = 1;

    const EXPRESSION_END = 2;

    const COMPARE = 10;

    const QUOTE = 20;

    const EQUALS = 21;

    const IDENTIFIER = 100;

    /**
     * {@inheritDoc}
     */
    protected function getCatchablePatterns()
    {
        return [
            '\[[a-z]+\]',
            '[\^\$a-z._0-9 |\p{L}]+',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getNonCatchablePatterns()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getModifiers()
    {
        return 'iu';
    }

    /**
     * {@inheritDoc}
     */
    protected function getType(&$value)
    {
        switch (strtolower(trim($value))) {
            case '(':
                $type = self::EXPRESSION_START;
                break;
            case ')':
                $type = self::EXPRESSION_END;
                break;
            case '[eq]':
            case '[regex]':
            case '[in]':
                $type = self::COMPARE;
                break;
            case '"':
                $type = self::QUOTE;
                break;
            case ':':
                $type = self::EQUALS;
                break;
            default:
                $type = self::IDENTIFIER;
        }

        return $type;
    }
}
