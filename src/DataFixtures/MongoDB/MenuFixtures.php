<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Menu;
use App\Services\FixtureLoader;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class MenuFixtures implements FixtureInterface
{
    private ?FixtureLoader $fixtureLoader = null;

    public function setFixtureLoader(FixtureLoader $fixtureLoader): void
    {
        $this->fixtureLoader = $fixtureLoader;
    }

    public function load(ObjectManager $entityManager): void
    {
        $fixtureLoader = $this->fixtureLoader ?? throw new \RuntimeException('FixtureLoader not set.');
        $menuDefinitions = $fixtureLoader->load('menu.yml');

        $faker = Factory::create();

        foreach ($menuDefinitions as $fixture) {
            for ($i = 0; $i < 11; $i++) {
                $menu = new Menu();

                $menu->setAgency(!empty($fixture['agency']) ? $fixture['agency'] : (string) mt_rand(100000, 999999));
                $menu->setEnabled(isset($fixture['enabled']) ? (bool) $fixture['enabled'] : $faker->boolean);
                $menu->setType(!empty($fixture['type']) ? $fixture['type'] : $faker->slug);
                $menu->setName(!empty($fixture['name']) ? $fixture['name'] : $faker->sentence);
                $menu->setMlid(!empty($fixture['mlid']) ? $fixture['mlid'] : mt_rand(1000, 9000));
                $menu->setOrder(isset($fixture['order']) ? (int) $fixture['order'] : mt_rand(-50, 50));
                $menu->setUrl(!empty($fixture['url']) ? $fixture['url'] : $faker->url);
                $menu->setPlid(mt_rand(0, 11));
                $menu->setItems(array_fill(
                    0,
                    mt_rand(1, 10),
                    mt_rand(1000, 9999),
                ));
                $menu->setWithImage((bool) mt_rand(0, 1));

                $entityManager->persist($menu);
            }
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
