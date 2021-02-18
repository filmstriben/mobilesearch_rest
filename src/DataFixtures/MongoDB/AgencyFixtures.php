<?php

namespace App\DataFixtures\MongoDB;

use Doctrine\Common\DataFixtures\FixtureInterface;
use App\Document\Agency;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class AgencyFixtures
 *
 * Prepares agency entries.
 */
class AgencyFixtures implements FixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
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
