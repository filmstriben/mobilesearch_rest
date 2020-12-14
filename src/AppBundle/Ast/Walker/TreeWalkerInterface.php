<?php

namespace AppBundle\Ast\Walker;

use AppBundle\Ast\NodeInterface;

/**
 * Interface TreeWalkerInterface.
 */
interface TreeWalkerInterface
{
    /**
     * Transforms an AST node into a query-like construct.
     *
     * @param \AppBundle\Ast\NodeInterface $node
     *
     * @return mixed
     */
    public function transform(NodeInterface $node);
}
