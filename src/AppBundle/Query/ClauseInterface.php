<?php

namespace AppBundle\Query;

/**
 * Interface ClauseInterface.
 */
interface ClauseInterface
{
    /**
     * Gets the comparison operator.
     *
     * @return string
     */
    public function getOperator();

    /**
     * Gets the field identifier.
     *
     * @return string
     */
    public function getField();

    /**
     * Gets the clause value.
     *
     * @return string
     */
    public function getValue();
}
