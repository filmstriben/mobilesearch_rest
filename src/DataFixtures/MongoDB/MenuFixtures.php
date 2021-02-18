<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Menu;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Faker\Factory;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class MenuFixtures
 *
 * Prepares menu entries.
 */
class MenuFixtures implements FixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $entityManager)
    {
        /** @var FixtureLoader $fixtureLoader */
        $fixtureLoader = $this->container->get('fixture_loader');
        $menuDefinitions = $fixtureLoader->load('menu.yml');

        $faker = Factory::create();

        foreach ($menuDefinitions as $fixture) {
            // Just generate 10 random menu entries. Whether specific fixture are needed,
            // remove this loop and insert fixture definitions into 'Resources/fixtures/menu.yml' file.
            for ($i = 0; $i < 11; $i++) {
                $menu = new Menu();

                $menu->setAgency(!empty($fixture['agency']) ? $fixture['agency'] : (string)mt_rand(100000, 999999));
                $menu->setEnabled(isset($fixture['enabled']) ? (bool)$fixture['enabled'] : $faker->boolean);
                $menu->setType(!empty($fixture['type']) ? $fixture['type'] : $faker->slug);
                $menu->setName(!empty($fixture['name']) ? $fixture['name'] : $faker->sentence);
                $menu->setMlid(!empty($fixture['mlid']) ? $fixture['mlid'] : mt_rand(1000, 5000));
                $menu->setOrder(isset($fixture['order']) ? (int)$fixture['order'] : mt_rand(-50, 50));
                $menu->setUrl(!empty($fixture['url']) ? $fixture['url'] : $faker->url);

                $entityManager->persist($menu);
            }
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
