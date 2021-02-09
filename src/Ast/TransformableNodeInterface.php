<?php

namespace App\Ast;

use App\Ast\Walker\TreeWalkerInterface;

/**
 * Interface TransformableNodeInterface.
 */
interface TransformableNodeInterface
{
    /**
     * Gets the query-like representation of this node using a given tree walker.
     *
     * @param \App\Ast\Walker\TreeWalkerInterface $walker
     *   Tree walker instance.
     */
    public function transform(TreeWalkerInterface $walker);
}
