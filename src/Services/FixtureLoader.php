<?php

namespace App\Services;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

/**
 * Class FixtureLoader
 *
 * Reads and parses fixture configs stored as yaml files.
 */
class FixtureLoader
{
    /**
     * Fixture base path.
     *
     * @var string
     */
    protected string $fixturesBasePath;

    /**
     * FixtureLoader constructor.
     *
     * @param string $fixturesBasePath
     *   Fixtures base path.
     */
    public function __construct(string $fixturesBasePath)
    {
        $this->fixturesBasePath = $fixturesBasePath;
    }

    /**
     * Loads and parses yaml fixture config.
     *
     * @param string $filename
     *   Fixture file name.
     *
     * @return array
     *   Parsed config.
     */
    public function load($filename)
    {
        $fileLocator = new FileLocator($this->fixturesBasePath);

        $fixtureFile = $fileLocator->locate($filename);
        if (is_readable($fixtureFile)) {
            return Yaml::parse(file_get_contents($fixtureFile));
        }

        return [];
    }
}
