<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Content
{
    /**
     * @MongoDB\id
     */
    protected $id;

    /**
     * @MongoDB\int
     */
    protected $nid;

    /**
     * @MongoDB\string
     */
    protected $agency;

    /**
     * @MongoDB\string
     */
    protected $type;

    /**
     * @MongoDB\Hash
     */
    protected $fields;

    /**
     * @MongoDB\Hash
     */
    protected $taxonomy;

    /**
     * @MongoDB\collection
     */
    protected $list;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set nid
     *
     * @param int $nid
     *
     * @return self
     */
    public function setNid($nid)
    {
        $this->nid = $nid;

        return $this;
    }

    /**
     * Get nid
     *
     * @return int $nid
     */
    public function getNid()
    {
        return $this->nid;
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
     * Set fields
     *
     * @param collection $fields
     *
     * @return self
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get fields
     *
     * @return collection $fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set taxonomy
     *
     * @param collection $taxonomy
     *
     * @return self
     */
    public function setTaxonomy($taxonomy)
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    /**
     * Get taxonomy
     *
     * @return collection $taxonomy
     */
    public function getTaxonomy()
    {
        return $this->taxonomy;
    }

    /**
     * Set list
     *
     * @param collection $list
     *
     * @return self
     */
    public function setList($list)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * Get list
     *
     * @return collection $list
     */
    public function getList()
    {
        return $this->list;
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
}
