<?php

namespace AppBundle\Query;

/**
 * Class Clause.
 */
class Clause implements ClauseInterface
{
    const OPERATOR_EQUALS = 'eq';

    const OPERATOR_REGEX = 'regex';

    const OPERATOR_IN = 'in';

    protected $field;

    protected $operator;

    protected $value;

    /**
     * Clause constructor.
     *
     * @param string $field
     *   Field identifier.
     * @param string $operator
     *   Comparison operator.
     * @param string $value
     *   Value to compare against.
     */
    public function __construct($field, $operator, $value)
    {
        $allowedOperators = [
            self::OPERATOR_EQUALS,
            self::OPERATOR_IN,
            self::OPERATOR_REGEX
        ];

        if (!in_array($operator, $allowedOperators)) {
            throw new \RuntimeException("Operator '{$operator}' is not supported.");
        }

        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }
}
