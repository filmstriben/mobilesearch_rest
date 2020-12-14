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

    public function getNodes()
    {
        return $this->nodes;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function appendChild($child)
    {
        $this->nodes[] = $child;
    }
}
