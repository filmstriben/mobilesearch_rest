<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use OpenApi\Annotations as OA;

#[ODM\Document]
class Menu
{
    /** @OA\Property(type="string") */
    #[ODM\Id]
    protected $id;

    /** @OA\Property(type="integer") */
    #[ODM\Field(type: 'int')]
    protected $mlid;

    /** @OA\Property(type="string") */
    #[ODM\Field(type: 'string')]
    protected $agency;

    /** @OA\Property(type="string") */
    #[ODM\Field(type: 'string')]
    protected $type;

    /** @OA\Property(type="string") */
    #[ODM\Field(type: 'string')]
    protected $name;

    /** @OA\Property(type="string") */
    #[ODM\Field(type: 'string')]
    protected $url;

    /** @OA\Property(type="integer", property="weight") */
    #[ODM\Field(type: 'int')]
    protected $order;

    /** @OA\Property(type="boolean") */
    #[ODM\Field(type: 'bool')]
    protected $enabled;

    /** @OA\Property(type="array", @OA\Items(type="integer")) */
    #[ODM\Field(type: 'collection')]
    protected $items;

    /** @OA\Property(type="integer") */
    #[ODM\Field(type: 'int')]
    protected $plid;

    /** @OA\Property(type="boolean") */
    #[ODM\Field(type: 'bool')]
    protected $with_image;

    /** @OA\Property(type="string") */
    #[ODM\Field(type: 'hash')]
    protected $image;

    /** @OA\Property(type="string") */
    #[ODM\Field(type: 'string')]
    protected $icon;

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

    /**
     * Get items.
     *
     * @return array
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    /**
     * Set items.
     *
     * @param array $items
     *
     * @return Menu
     */
    public function setItems(array $items): self
    {
        $this->items = array_values($items);

        return $this;
    }

    /**
     *  Get parent id.
     *
     * @return integer
     */
    public function getPlid(): ?int
    {
        return $this->plid;
    }

    /**
     * Set parent id.
     *
     * @param integer $plid
     *
     * @return Menu
     */
    public function setPlid(int $plid): self
    {
        $this->plid = $plid;

        return $this;
    }

    /**
     * Get with image parameter.
     *
     * @return boolean
     */
    public function getWithImage(): ?bool
    {
        return $this->with_image;
    }

    /**
     * Set with image parameter.
     *
     * @param bool $with_image
     *
     * @return Menu
     */
    public function setWithImage(bool $with_image): self
    {
        $this->with_image = $with_image;

        return $this;
    }

    /**
     * Get background image url.
     *
     * @return array
     */
    public function getImage(): ?array
    {
        return $this->image;
    }

    /**
     * Set background image url.
     *
     * @param array $image
     *
     * @return Menu
     */
    public function setImage(array $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get icon.
     *
     * @return string
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Set icon.
     *
     * @param string|null $icon
     *
     * @return Menu
     */
    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
}
