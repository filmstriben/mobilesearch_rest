<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="App\Repositories\ServiceHitRepository")
 */
class ServiceHit
{
    /**
     * @MongoDB\Id()
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $type;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $time;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $hits;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $url;

    /**
     * Gets object id.
     *
     * @return \Doctrine\ODM\MongoDB\Mapping\Annotations\Id
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
     * @return \MongoDB\BSON\UTCDateTime
     *   Hit timestamp.
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Sets hit time.
     *
     * @param \MongoDB\BSON\UTCDateTime $time
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

    /**
     * Gets request url.
     *
     * @return string
     *   URL string.
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets request url.
     *
     * @param string $url
     *   URL string.
     *
     * @return $this
     *   This object.
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }
}
