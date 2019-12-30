<?php

namespace AppBundle\Repositories;

use AppBundle\Document\Content;
use AppBundle\Document\Lists;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class ListsRepository.
 */
class ListsRepository extends DocumentRepository
{
    /**
     * Filter items in the list that are not of certain type.
     *
     * @param Lists $list
     *   The list object.
     * @param string $itemType
     *   Item type to keep.
     * @return Lists
     *   Altered list object.
     */
    public function filterAttachedItems(Lists $list, $itemType)
    {
        $nids = $list->getNids();
        $qb = $this->getDocumentManager()->createQueryBuilder(Content::class);

        $qb->distinct('nid');
        $qb->field('nid')->in($nids);
        $qb->field('type')->equals($itemType);

        $contentItems = $qb->getQuery()->execute();

        $list->setNids(array_values(array_intersect($nids, $contentItems->toArray())));

        return $list;
    }

    /**
     * Finds related list items containing specified node ids.
     *
     * @param array $nodeIds
     *   A set of node id's that list might contain.
     *
     * @return Lists[]
     */
    public function findAttached(Content $node) {
        $qb = $this
            ->createQueryBuilder()
            ->field('agency')->equals($node->getAgency())
            ->field('nids')->in([$node->getNid()]);

        return $qb->getQuery()->execute();
    }
}
