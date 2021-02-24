<?php

namespace AppBundle\Ast;

/**
 * Class AbstractNode.
 */
abstract class AbstractNode implements NodeInterface
{
    const OPERATOR_AND = 'and';

    const OPERATOR_OR = 'or';

    protected $operator;

    protected $nodes;

    /**
     * {@inheritDoc}
     */
    public function getNodes()
    {
        return $this->nodes;
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
    public function appendChild($child)
    {
        $this->nodes[] = $child;
    }
}
