<?php

namespace App\DataFixtures\MongoDB;

use Document\Configuration;
use Services\FixtureLoader;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AgencyFixtures.
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
            $configuration->setAgency($fixture['agencyId']);
            $configuration->setSettings($fixture['settings']);

            $manager->persist($configuration);
        }

        $manager->flush();
    }
}