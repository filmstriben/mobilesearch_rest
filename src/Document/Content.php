<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use OpenApi\Annotations as OA;

/**
 * @MongoDB\Document(repositoryClass="App\Repositories\ContentRepository")
 */
class Content
{
    /**
     * @MongoDB\Id()
     * @OA\Property(type="string")
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")
     * @OA\Property(type="integer")
     */
    protected $nid;

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
     * @MongoDB\Field(type="hash")
     * @OA\Property(type="array", @OA\Items(type="object"))
     */
    protected $fields;

    /**
     * @MongoDB\Field(type="hash")
     * @OA\Property(type="array", @OA\Items(type="object"))
     */
    protected $taxonomy;

    /**
     * @MongoDB\Field(type="hash")
     * @OA\Property(type="array", @OA\Items(type="object"))
     */
    protected $list;

    /**
     * @MongoDB\Field(type="float", notSaved=true)
     * @OA\Property(type="number")
     */
    public $score;

    /**
     * Sets score.
     *
     * @param float $score
     *
     * @return $this
     */
    public function setScore($score) {
        $this->score = $score;

        return $this;
    }

    /**
     * Gets score.
     *
     * @return float
     */
    public function getScore() {
        return $this->score;
    }

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
     * @param array $fields
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
     * @return array $fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set taxonomy
     *
     * @param array $taxonomy
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
     * @return array $taxonomy
     */
    public function getTaxonomy()
    {
        return $this->taxonomy;
    }

    /**
     * Set list
     *
     * @param array $list
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
     * @return array $list
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

    public function toArray($withScore = false) {
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
