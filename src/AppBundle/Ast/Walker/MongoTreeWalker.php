<?php

namespace AppBundle\Ast\Walker;

use AppBundle\Ast\NodeInterface;
use AppBundle\Query\Clause;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\MongoDB\Query\Expr;

/**
 * Class MongoTreeWalker.
 */
class MongoTreeWalker implements TreeWalkerInterface
{
    protected $queryBuilder;

    /**
     * MongoTreeWalker constructor.
     *
     * @param \Doctrine\ODM\MongoDB\Query\Builder $qb
     *   Query builder to alter.
     */
    public function __construct(Builder $qb)
    {
        $this->queryBuilder = $qb;
    }

    /**
     * {@inheritDoc}
     */
    public function transform(NodeInterface $node)
    {
        $expr = $this->walkTree($node, new Expr());
        $this->queryBuilder->addAnd($expr);
    }

    /**
     * Walks the AST tree recursively to build a mongo query expression.
     *
     * @param \AppBundle\Ast\NodeInterface $node
     *   Root node.
     * @param \Doctrine\MongoDB\Query\Expr $expr
     *   Expression to build.
     *
     * @return \Doctrine\MongoDB\Query\Expr
     *   Result expression.
     */
    private function walkTree(NodeInterface $node, Expr $expr)
    {
        $expressions = [];
        foreach ($node->getNodes() as $childNode) {
            if ($childNode instanceof NodeInterface) {
                $expressions[] = $this->walkTree($childNode, new Expr());
            } elseif ($childNode instanceof Clause) {
                $expression = new Expr();
                $expression->field($childNode->getField());
                $value = $childNode->getValue();

                // TODO: 'nid' is hardcoded, find a way to read this dynamically from entity class metadata.
                if ('nid' == $expression->getCurrentField()) {
                    $value = ('in' == $childNode->getOperator()) ? array_map(function ($v) {
                        return (int) $v;
                    }, explode('|', $value)) : (int) $value;
                }

                switch ($childNode->getOperator()) {
                    case 'in':
                        $expression->in($value);
                        break;
                    case 'eq':
                        $expression->equals($value);
                        break;
                    case 'regex':
                        $expression->equals(new \MongoRegex("/{$value}/i"));
                        break;
                }

                $expressions[] = $expression;
            }
        }

        switch ($node->getOperator()) {
            case 'and':
                call_user_func_array([$expr, 'addAnd'], $expressions);
                break;
            case 'or':
                call_user_func_array([$expr, 'addOr'], $expressions);
                break;
        }

        return $expr;
    }
}
