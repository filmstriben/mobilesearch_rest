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
     * Finds lists containing the respective content node.
     *
     * @param Content $node
     *   Content entity as main search criteria.
     * @param boolean $withAgency
     *   Seek lists from same agency.
     *
     * @return Lists[]
     */
    public function findAttached(Content $node, $withAgency = false) {
        $qb = $this
            ->createQueryBuilder()
            ->field('nids')->in([$node->getNid()]);

        if ($withAgency) {
            $qb->field('agency')->equals($node->getAgency());
        }

        return $qb->getQuery()->execute();
    }
}
