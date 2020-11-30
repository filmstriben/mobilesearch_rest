<?php

namespace AppBundle\DataFixtures\MongoDB;

use AppBundle\Document\Lists;
use AppBundle\Services\FixtureLoader;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Class ListsFixtures
 *
 * Prepares list entries.
 */
class ListsFixtures extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $entityManager)
    {
        /** @var FixtureLoader $fixtureLoader */
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
