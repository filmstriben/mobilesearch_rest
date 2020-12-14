<?php

namespace AppBundle\Ast;

/**
 * Interface NodeInterface.
 */
interface NodeInterface
{
    /**
     * Gets the comparison operator.
     *
     * @return string
     */
    public function getOperator();

    public function getNodes();

    public function appendChild($child);
}
