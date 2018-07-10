<?php

namespace AppBundle\DataFixtures\MongoDB;

use AppBundle\Document\Agency;
use AppBundle\Services\FixtureLoader;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AgencyFixtures extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var FixtureLoader $fixtureLoader */
        $fixtureLoader = $this->container->get('fixture_loader');
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
