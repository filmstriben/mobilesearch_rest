<?php

namespace App\DataFixtures\MongoDB;

use Doctrine\Common\DataFixtures\FixtureInterface;
use App\Document\Content;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class ContentFixtures
 *
 * Prepares content entries.
 */
class ContentFixtures implements FixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $fixtureLoader = $this->container->get('fixture_loader');
        $osDefinitions = $fixtureLoader->load('os.yml');
        $editorialDefinitions = $fixtureLoader->load('editorial.yml');

        $faker = Factory::create();
        $now = time();

        foreach (array_merge($osDefinitions, $editorialDefinitions) as $fixture) {
            $content = new Content();

            // Set some random values for fields which are required and defined as null.
            $content->setNid(isset($fixture['nid']) ? $fixture['nid'] : mt_rand());
            $content->setAgency(isset($fixture['agency']) ? $fixture['agency'] : mt_rand(100000, 999999));
            $content->setType(isset($fixture['type']) ? $fixture['type'] : $faker->word);

            foreach ($fixture['fields'] as $field => &$values) {
                if (is_null($values['value'])) {
                    switch ($field) {
                        case 'title':
                            $value = $faker->sentence;
                            break;
                        case 'publish_date':
                            // Assume creation time couple of hours ago.
                            $value = gmdate('c', $now - mt_rand(1, 5) * 86400);
                            break;
                        default:
                            $value = null;
                    }

                    $values['value'] = $value;
                }
            }

            $content->setFields(isset($fixture['fields']) ? $fixture['fields'] : []);
            $content->setTaxonomy(isset($fixture['taxonomy']) ? $fixture['taxonomy'] : []);
            $content->setList(isset($fixture['list']) ? $fixture['list'] : []);

            $manager->persist($content);
        }

        $manager->flush();
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
