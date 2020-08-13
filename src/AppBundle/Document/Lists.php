<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repositories\ListsRepository")
 */
class Lists
{
    /**
     * @MongoDB\id
     */
    protected $id;

    /**
     * @MongoDB\int
     */
    protected $lid;

    /**
     * @MongoDB\collection
     */
    protected $agency;

    /**
     * @MongoDB\string
     */
    protected $name;

    /**
     * @MongoDB\string
     */
    protected $type;

    /**
     * @MongoDB\boolean
     */
    protected $promoted;

    /**
     * @MongoDB\integer
     */
    protected $weight;

    /**
     * @MongoDB\Hash()
     */
    protected $criteria;

    /**
     * Gets internal id.
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets list id.
     *
     * @param int $lid
     *
     * @return $this
     */
    public function setLid($lid)
    {
        $this->lid = $lid;

        return $this;
    }

    /**
     * Gets list id.
     *
     * @return int
     */
    public function getLid()
    {
        return $this->lid;
    }

    /**
     * Set agency
     *
     * @param array $agency
     *
     * @return $this
     */
    public function setAgency(array $agency)
    {
        $this->agency = $agency;

        return $this;
    }

    /**
     * Get agency
     *
     * @return array
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return $this
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
     * Set type
     *
     * @param string $type
     *
     * @return $this
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
     * Set promoted
     *
     * @param boolean $promoted
     *
     * @return $this
     */
    public function setPromoted($promoted)
    {
        $this->promoted = $promoted;

        return $this;
    }

    /**
     * Get promoted
     *
     * @return boolean $promoted
     */
    public function getPromoted()
    {
        return $this->promoted;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     *
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer $weight
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Sets list criteria.
     *
     * @param array $criteria
     *
     * @return $this
     */
    public function setCriteria(array $criteria)
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * Gets list criteria.
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }
}
