<?php

namespace AppBundle\Ast;

use AppBundle\Ast\Walker\TreeWalkerInterface;

/**
 * Interface TransformableNodeInterface.
 */
interface TransformableNodeInterface
{
    /**
     * Gets the query-like representation of this node using a given tree walker.
     *
     * @param \AppBundle\Ast\Walker\TreeWalkerInterface $walker
     *
     * @return mixed
     */
    public function transform(TreeWalkerInterface $walker);
}
