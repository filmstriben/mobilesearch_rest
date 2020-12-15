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

    /**
     * Gets the child nodes.
     *
     * @return array
     */
    public function getNodes();

    /**
     * Appends a child node.
     *
     * @param mixed $child
     *
     * TODO: Force and typehint the child type.
     */
    public function appendChild($child);
}
