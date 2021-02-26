<?php

namespace App\Ast\Walker;

use App\Ast\NodeInterface;

/**
 * Interface TreeWalkerInterface.
 */
interface TreeWalkerInterface
{
    /**
     * Transforms an AST into a query-like construct.
     *
     * @param \App\Ast\NodeInterface $node
     */
    public function transform(NodeInterface $node);
}
