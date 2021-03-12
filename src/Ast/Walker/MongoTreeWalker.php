<?php

namespace App\Ast\Walker;

use App\Ast\NodeInterface;
use App\Query\Clause;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use MongoDB\BSON\Regex as MongoRegex;

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
        $expr = $this->walkTree($node, $this->queryBuilder->expr());
        $this->queryBuilder->addAnd($expr);
    }

    /**
     * Walks the AST tree recursively to build a mongo query expression.
     *
     * @param \App\Ast\NodeInterface $node
     *   Root node.
     * @param \Doctrine\ODM\MongoDB\Query\Expr $expr
     *   Expression to build.
     *
     * @return \Doctrine\ODM\MongoDB\Query\Expr
     *   Result expression.
     */
    private function walkTree(NodeInterface $node, Expr $expr)
    {
        $expressions = [];
        foreach ($node->getNodes() as $childNode) {
            if ($childNode instanceof NodeInterface) {
                $expressions[] = $this->walkTree($childNode, $this->queryBuilder->expr());
            } elseif ($childNode instanceof Clause) {
                $expression = $this->queryBuilder->expr();
                $expression->field($childNode->getField());
                $value = $childNode->getValue();

                switch ($childNode->getOperator()) {
                    case 'in':
                        $value = explode('|', $value);

                        // TODO: 'nid' is hardcoded, find a way to read this dynamically from entity class metadata.
                        if ('nid' == $expression->getCurrentField()) {
                            $value = array_map(function ($v) {
                                return (int) $v;
                            }, $value);
                        }

                        $expression->in($value);
                        break;
                    case 'eq':
                        // TODO: 'nid' is hardcoded, find a way to read this dynamically from entity class metadata.
                        if ('nid' == $expression->getCurrentField()) {
                            $value = (int) $value;
                        }

                        $expression->equals($value);
                        break;
                    case 'regex':
                        $expression->equals(new MongoRegex($value, 'i'));
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
