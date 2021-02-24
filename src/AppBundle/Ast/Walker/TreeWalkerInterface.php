<?php

namespace AppBundle\Ast\Walker;

use AppBundle\Ast\NodeInterface;

/**
 * Interface TreeWalkerInterface.
 */
interface TreeWalkerInterface
{
    /**
     * Transforms an AST into a query-like construct.
     *
     * @param \AppBundle\Ast\NodeInterface $node
     */
    public function transform(NodeInterface $node);
}
