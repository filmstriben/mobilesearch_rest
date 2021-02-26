<?php

namespace App\Repositories;

use App\Document\Content;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

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
     * @param int $external
     *   External status.
     * @param bool $countOnly
     *   Perform a count query instead.
     *
     * @return Content[]
     *   A set of suggested entities.
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function fetchSuggestions($query, $amount = 10, $skip = 0, $external = null, $countOnly = false)
    {
        /** @var \Doctrine\ODM\MongoDB\Query\Builder $qb */
        $qb = $this
            ->getDocumentManager()
            ->createQueryBuilder(Content::class);

        if (null !== $external) {
            $qb->field('fields.field_external.value')->equals((string) $external);
        }

        if ($countOnly) {
            $qb->count();
        } else {
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
