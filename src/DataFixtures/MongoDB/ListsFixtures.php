<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Lists;
use App\Services\FixtureLoader;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ListsFixtures implements FixtureInterface
{
    private ?FixtureLoader $fixtureLoader = null;

    public function setFixtureLoader(FixtureLoader $fixtureLoader): void
    {
        $this->fixtureLoader = $fixtureLoader;
    }

    public function load(ObjectManager $entityManager): void
    {
        $fixtureLoader = $this->fixtureLoader ?? throw new \RuntimeException('FixtureLoader not set.');
        $listsDefinitions = $fixtureLoader->load('lists.yml');

        $faker = Factory::create();

        foreach ($listsDefinitions as $fixture) {
            $list = new Lists();

            $list->setAgency(!empty($fixture['agency']) ? $fixture['agency'] : [mt_rand(100000, 999999)]);
            $list->setLid(!empty($fixture['lid']) ? $fixture['lid'] : mt_rand());
            $list->setName(!empty($fixture['name']) ? $fixture['name'] : $faker->title);
            $list->setType(!empty($fixture['type']) ? $fixture['type'] : $faker->word);
            $list->setPromoted(isset($fixture['promoted']) ? (bool) $fixture['promoted'] : $faker->boolean);
            $list->setWeight(isset($fixture['weight']) ? $fixture['weight'] : mt_rand(-50, 50));
            $list->setCriteria(isset($fixture['criteria']) ? $fixture['criteria'] : []);

            $entityManager->persist($list);
        }

        $entityManager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AgencyFixtures::class,
        ];
    }
}
