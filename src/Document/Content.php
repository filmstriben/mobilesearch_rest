<?php

namespace App\Document;

use App\Repositories\ContentRepository;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use OpenApi\Annotations as OA;

#[ODM\Document(repositoryClass: ContentRepository::class)]
class Content
{
    /**
     * @OA\Property(type="string")
     */
    #[ODM\Id]
    protected $id;

    /**
     * @OA\Property(type="integer")
     */
    #[ODM\Field(type: 'int')]
    protected $nid;

    /**
     * @OA\Property(type="string")
     */
    #[ODM\Field(type: 'string')]
    protected $agency;

    /**
     * @OA\Property(type="string")
     */
    #[ODM\Field(type: 'string')]
    protected $type;

    /**
     * @OA\Property(type="array", @OA\Items(type="object"))
     */
    #[ODM\Field(type: 'hash')]
    protected $fields;

    /**
     * @OA\Property(type="array", @OA\Items(type="object"))
     */
    #[ODM\Field(type: 'hash')]
    protected $taxonomy;

    /**
     * @OA\Property(type="array", @OA\Items(type="object"))
     */
    #[ODM\Field(type: 'hash')]
    protected $list;

    /**
     * @OA\Property(type="number")
     */
    #[ODM\Field(type: 'float', notSaved: true)]
    public $score;

    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setNid($nid)
    {
        $this->nid = $nid;

        return $this;
    }

    public function getNid()
    {
        return $this->nid;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setTaxonomy($taxonomy)
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    public function getTaxonomy()
    {
        return $this->taxonomy;
    }

    public function setList($list)
    {
        $this->list = $list;

        return $this;
    }

    public function getList()
    {
        return $this->list;
    }

    public function setAgency($agency)
    {
        $this->agency = $agency;

        return $this;
    }

    public function getAgency()
    {
        return $this->agency;
    }

    public function toArray($withScore = false)
    {
        $return = [
            'id' => $this->getId(),
            'nid' => $this->getNid(),
            'agency' => $this->getAgency(),
            'type' => $this->getType(),
            'fields' => $this->getFields(),
            'taxonomy' => $this->getTaxonomy(),
            'list' => $this->getList(),
        ];

        if ($withScore) {
            $return['score'] = $this->getScore();
        }

        return $return;
    }
}
