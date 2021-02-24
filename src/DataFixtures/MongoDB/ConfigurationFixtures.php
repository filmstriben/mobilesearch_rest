<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Configuration;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class AgencyFixtures.
 *
 * Prepares agency entries.
 */
class ConfigurationFixtures implements FixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
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
