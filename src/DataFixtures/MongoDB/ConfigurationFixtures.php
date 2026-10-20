<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Configuration;
use App\Services\FixtureLoader;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ConfigurationFixtures implements FixtureInterface
{
    private ?FixtureLoader $fixtureLoader = null;

    public function setFixtureLoader(FixtureLoader $fixtureLoader): void
    {
        $this->fixtureLoader = $fixtureLoader;
    }

    public function load(ObjectManager $manager): void
    {
        $fixtureLoader = $this->fixtureLoader ?? throw new \RuntimeException('FixtureLoader not set.');
        $agencyDefinitions = $fixtureLoader->load('configuration.yml');

        foreach ($agencyDefinitions as $fixture) {
            $configuration = new Configuration();
            $configuration->setAgency($fixture['agencyId']);
            $configuration->setSettings($fixture['settings']);

            $manager->persist($configuration);
        }

        $manager->flush();
    }
}
