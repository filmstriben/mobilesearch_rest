<?php

namespace AppBundle\Repositories;

use AppBundle\Document\Content;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class ContentRepository.
 */
class ContentRepository extends DocumentRepository
{
    /**
     * Searches content suggestions based on certain criteria.
     *
     * @param string $query
     *   Search query.
     * @param int $amount
     *   Fetch this amount of suggestions.
     * @param int $skip
     *   Skip this amount of suggestions.
     * @param bool $countOnly
     *   Perform a count query instead.
     *
     * @return Content[]
     *   A set of suggested entities.
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function fetchSuggestions($query, $amount = 10, $skip = 0, $countOnly = FALSE)
    {
        /** @var \Doctrine\ODM\MongoDB\Query\Builder $qb */
        $qb = $this
            ->getDocumentManager()
            ->createQueryBuilder(Content::class);

        if ($countOnly) {
            $qb->count();
        }
        else {
            $qb
                ->skip($skip)
                ->limit($amount)
                ->selectMeta('score', 'textScore')
                ->sortMeta('score', 'textScore');
        }

        $qb->text($query);

        return $qb->getQuery()->execute();
    }
}
