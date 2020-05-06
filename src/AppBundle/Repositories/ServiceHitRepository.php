<?php

namespace AppBundle\Repositories;

use AppBundle\Document\ServiceHit;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class ContentRepository.
 */
class ServiceHitRepository extends DocumentRepository
{
    /**
     * Tracks hits count for a certain type.
     *
     * Granularity is 1s.
     *
     * @param string $type
     *   Hit type.
     */
    public function trackHit($type) {
        $now = new \MongoDate(gmdate('U'));

        /** @var \AppBundle\Document\ServiceHit $hit */
        $hit = $this->findOneBy(
            [
                'type' => $type,
                'time' => $now,
            ]
        );

        if (!$hit) {
            $hit = new ServiceHit();
            $hit
                ->setType($type)
                ->setHits(1)
                ->setTime($now);

            $this->getDocumentManager()->persist($hit);
        }
        else {
            $hits = $hit->getHits();
            $hits++;
            $hit->setHits($hits);
        }

        $this->getDocumentManager()->flush();
    }
}
