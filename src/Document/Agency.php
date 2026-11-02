<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document]
class Agency
{
    #[ODM\Id]
    protected $id;

    #[ODM\Field(type: 'string')]
    protected $agencyId;

    #[ODM\Field(type: 'string')]
    protected $key;

    #[ODM\Field(type: 'string')]
    protected $name;

    #[ODM\Field(type: 'collection')]
    protected $children;

    public function getId()
    {
        return $this->id;
    }

    public function setAgencyId($agencyId)
    {
        $this->agencyId = $agencyId;

        return $this;
    }

    public function getAgencyId()
    {
        return $this->agencyId;
    }

    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

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
