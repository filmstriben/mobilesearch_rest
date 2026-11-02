<?php

namespace App\Document;

use App\Repositories\ServiceHitRepository;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(repositoryClass: ServiceHitRepository::class)]
class ServiceHit
{
    #[ODM\Id]
    protected $id;

    #[ODM\Field(type: 'string')]
    protected $type;

    #[ODM\Field(type: 'date')]
    protected $time;

    #[ODM\Field(type: 'int')]
    protected $hits;

    #[ODM\Field(type: 'string')]
    protected $url;

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }
}
