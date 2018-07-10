<?php

namespace AppBundle\Services;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

/**
 * Class FixtureLoader
 *
 * Reads and parses yaml fixture configs.
 */
class FixtureLoader
{
    /**
     * Loads and parses yaml fixture config.
     *
     * @param string $filename  Fixture file name.
     * @return array            Parsed config.
     */
    public function load($filename)
    {
        $fileLocator = new FileLocator(__DIR__.'/../Resources/fixtures');

        $fixtureFile = $fileLocator->locate($filename);
        if (is_readable($fixtureFile)) {
            return Yaml::parse(file_get_contents($fixtureFile));
        }

        return [];
    }
}
