<?php

namespace App\Repositories;

use App\Document\ServiceHit;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use MongoDB\BSON\UTCDateTime;

/**
 * Class ContentRepository.
 */
class ServiceHitRepository extends DocumentRepository
{
    /**
     * Tracks hits for a certain request type.
     *
     * @param string $type
     *   Hit type.
     */
    public function trackHit($type, $url)
    {
        $now = new UTCDateTime(gmdate('U'));

        $hit = new ServiceHit();
        $hit
            ->setType($type)
            ->setHits(1)
            ->setTime($now)
            ->setUrl($url);

        $this->getDocumentManager()->persist($hit);
        $this->getDocumentManager()->flush();
    }
}
