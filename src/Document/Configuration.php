<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document]
class Configuration
{
    #[ODM\Id]
    protected $id;

    #[ODM\Field(type: 'string')]
    protected $agency;

    #[ODM\Field(type: 'hash')]
    protected $settings;

    public function getId()
    {
        return $this->id;
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

    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function mergeSettings(array $settings)
    {
        if (empty($this->settings)) {
            $this->settings = [];
        }
        foreach ($settings as $k => $v) {
            $this->settings[$k] = $v;
        }

        return $this;
    }
}
