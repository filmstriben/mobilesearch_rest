<?php

namespace AppBundle\DataFixtures\MongoDB;

use AppBundle\Document\Lists;
use AppBundle\Services\FixtureLoader;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

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

            $list->setAgency(!empty($fixture['agency']) ? $fixture['agency'] : mt_rand(100000, 999999));
            $list->setKey(!empty($fixture['key']) ? $fixture['key'] : md5(mt_rand()));
            $list->setName(!empty($fixture['name']) ? $fixture['name'] : $faker->title);
            $list->setNids(!empty($fixture['nids']) ? $fixture['nids'] : range(mt_rand(1000, 1005), mt_rand(1006, 1010)));
            $list->setType(!empty($fixture['type']) ? $fixture['type'] : $faker->word);
            $list->setPromoted(isset($fixture['promoted']) ? (bool)$fixture['promoted'] : $faker->boolean);
            $list->setWeight(isset($fixture['weight']) ? $fixture['weight'] : mt_rand(-50, 50));

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
