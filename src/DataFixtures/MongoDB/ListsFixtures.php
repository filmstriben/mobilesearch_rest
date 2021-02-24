<?php

namespace App\DataFixtures\MongoDB;

use Doctrine\Common\DataFixtures\FixtureInterface;
use App\Document\Lists;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class ListsFixtures
 *
 * Prepares list entries.
 */
class ListsFixtures implements FixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $entityManager)
    {
        $fixtureLoader = $this->container->get('fixture_loader');
        $listsDefinitions = $fixtureLoader->load('lists.yml');

        $faker = Factory::create();

        foreach ($listsDefinitions as $fixture) {
            $list = new Lists();

            $list->setAgency(!empty($fixture['agency']) ? $fixture['agency'] : [mt_rand(100000, 999999)]);
            $list->setLid(!empty($fixture['lid']) ? $fixture['lid'] : mt_rand());
            $list->setName(!empty($fixture['name']) ? $fixture['name'] : $faker->title);
            $list->setType(!empty($fixture['type']) ? $fixture['type'] : $faker->word);
            $list->setPromoted(isset($fixture['promoted']) ? (bool)$fixture['promoted'] : $faker->boolean);
            $list->setWeight(isset($fixture['weight']) ? $fixture['weight'] : mt_rand(-50, 50));
            $list->setCriteria(isset($fixture['criteria']) ? $fixture['criteria'] : []);

            $entityManager->persist($list);
        }

        $entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            AgencyFixtures::class,
        ];
    }
}
