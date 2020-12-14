<?php

namespace AppBundle\Ast;

use AppBundle\Ast\Walker\TreeWalkerInterface;

/**
 * Class Node.
 */
class Node extends AbstractNode implements TransformableNodeInterface
{
    /**
     * Node constructor.
     *
     * @param string $operator
     * @param array $childNodes
     */
    public function __construct($operator, array $childNodes)
    {
        $this->operator = in_array($operator, [self::OPERATOR_AND, self::OPERATOR_OR]) ? $operator : self::OPERATOR_AND;
        $this->nodes = $childNodes;
    }

    /**
     * {@inheritDoc}
     */
    public function transform(TreeWalkerInterface $walker)
    {
        return $walker->transform($this);
    }
}
