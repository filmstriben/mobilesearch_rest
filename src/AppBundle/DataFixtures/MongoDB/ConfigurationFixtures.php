<?php

namespace AppBundle\DataFixtures\MongoDB;

use AppBundle\Document\Configuration;
use AppBundle\Services\FixtureLoader;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AgencyFixtures
 *
 * Prepares agency entries.
 */
class ConfigurationFixtures extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var FixtureLoader $fixtureLoader */
        $fixtureLoader = $this->container->get('fixture_loader');
        $agencyDefinitions = $fixtureLoader->load('configuration.yml');

        foreach ($agencyDefinitions as $fixture) {
            $configuration = new Configuration();
            $configuration->setAgencyId($fixture['agencyId']);
            $configuration->setSettings($fixture['settings']);

            $manager->persist($configuration);
        }

        $manager->flush();
    }
}
