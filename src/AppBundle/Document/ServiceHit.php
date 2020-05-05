<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repositories\ServiceHitRepository")
 */
class ServiceHit {

    /**
     * @MongoDB\Id()
     */
    protected $id;

    /**
     * @MongoDB\String()
     */
    protected $type;

    /**
     * @MongoDB\Date()
     */
    protected $time;

    /**
     * @MongoDB\Integer()
     */
    protected $hits;

    /**
     * Gets object id.
     *
     * @return string
     *   Object id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets hit type.
     *
     * @return string
     *   Hit type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets hit type.
     *
     * @param string $type
     *   Hit type.
     *
     * @return $this
     *   This object.
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets hit time.
     *
     * @return \MongoDate
     *   Hit timestamp.
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Sets hit time.
     *
     * @param \MongoDate $time
     *   Hit timestamp.
     *
     * @return $this
     *   This object.
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Gets number of hits.
     *
     * @return integer
     *   Number of hits.
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Sets number of hits.
     *
     * @param integer $hits
     *   Number of hits.
     *
     * @return $this
     *   This object.
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

}
