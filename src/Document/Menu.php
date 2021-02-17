<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use OpenApi\Annotations as OA;

/**
 * @MongoDB\Document
 */
class Menu
{
    /**
     * @MongoDB\id
     * @OA\Property(type="string")
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")
     * @OA\Property(type="integer")
     */
    protected $mlid;

    /**
     * @MongoDB\Field(type="string")
     * @OA\Property(type="string")
     */
    protected $agency;

    /**
     * @MongoDB\Field(type="string")
     * @OA\Property(type="string")
     */
    protected $type;

    /**
     * @MongoDB\Field(type="string")
     * @OA\Property(type="string")
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     * @OA\Property(type="string")
     */
    protected $url;

    /**
     * @MongoDB\Field(type="int")
     * @OA\Property(type="integer")
     */
    protected $order;

    /**
     * @MongoDB\Field(type="int")
     * @OA\Property(type="boolean")
     */
    protected $enabled;

    /**
     * Get id
     *
     * @return \Doctrine\ODM\MongoDB\Mapping\Annotations\Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set mlid
     *
     * @param int $mlid
     *
     * @return self
     */
    public function setMlid($mlid)
    {
        $this->mlid = $mlid;

        return $this;
    }

    /**
     * Get mlid
     *
     * @return int $mlid
     */
    public function getMlid()
    {
        return $this->mlid;
    }

    /**
     * Set agency
     *
     * @param string $agency
     *
     * @return self
     */
    public function setAgency($agency)
    {
        $this->agency = $agency;

        return $this;
    }

    /**
     * Get agency
     *
     * @return string $agency
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set order
     *
     * @param int $order
     *
     * @return self
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return int $order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}
