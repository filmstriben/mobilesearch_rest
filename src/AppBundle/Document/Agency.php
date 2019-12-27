<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Agency
{
    /**
     * @MongoDB\id
     */
    protected $id;

    /**
     * @MongoDB\string
     */
    protected $agencyId;

    /**
     * @MongoDB\string
     */
    protected $key;

    /**
     * @MongoDB\string
     */
    protected $name;

    /**
     * @MongoDB\collection
     */
    protected $children;

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
     * Set agencyId
     *
     * @param string $agencyId
     *
     * @return self
     */
    public function setAgencyId($agencyId)
    {
        $this->agencyId = $agencyId;

        return $this;
    }

    /**
     * Get agencyId
     *
     * @return string $agencyId
     */
    public function getAgencyId()
    {
        return $this->agencyId;
    }

    /**
     * Set key
     *
     * @param string $key
     *
     * @return self
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string $key
     */
    public function getKey()
    {
        return $this->key;
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
     * Set children
     *
     * @param collection $children
     *
     * @return self
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Get children
     *
     * @return collection $children
     */
    public function getChildren()
    {
        if (empty($this->children)) {
            $this->children = [];
        }

        return array_map(function ($v) {
            return (string) $v;
        }, $this->children);
    }
}
