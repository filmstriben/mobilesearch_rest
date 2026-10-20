<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Agency;
use App\Services\FixtureLoader;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AgencyFixtures implements FixtureInterface
{
    private ?FixtureLoader $fixtureLoader = null;

    public function setFixtureLoader(FixtureLoader $fixtureLoader): void
    {
        $this->fixtureLoader = $fixtureLoader;
    }

    public function load(ObjectManager $manager): void
    {
        $fixtureLoader = $this->fixtureLoader ?? throw new \RuntimeException('FixtureLoader not set.');
        $agencyDefinitions = $fixtureLoader->load('agencies.yml');

        foreach ($agencyDefinitions as $fixture) {
            $agency = new Agency();
            $agency->setAgencyId($fixture['agencyId']);
            $agency->setChildren($fixture['children']);
            $agency->setKey($fixture['key']);
            $agency->setName($fixture['name']);

            $manager->persist($agency);
        }

        $manager->flush();
    }
}
