<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document()
 */
class Configuration
{
    /**
     * @MongoDB\Id()
     */
    protected $id;

    /**
     * @MongoDB\String()
     */
    protected $agency;

    /**
     * @MongoDB\Hash()
     */
    protected $settings;

    /**
     * Get id.
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set agency.
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
     * Get agency.
     *
     * @return string $agency
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * Set settings.
     *
     * @param collection $settings
     *
     * @return self
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get settings.
     *
     * @return collection $settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Merge settings.
     *
     * @param array $settings

     * @return self
     */
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
