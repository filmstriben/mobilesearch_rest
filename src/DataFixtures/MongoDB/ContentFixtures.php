<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Content;
use App\Services\FixtureLoader;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ContentFixtures implements FixtureInterface
{
    private ?FixtureLoader $fixtureLoader = null;

    public function setFixtureLoader(FixtureLoader $fixtureLoader): void
    {
        $this->fixtureLoader = $fixtureLoader;
    }

    public function load(ObjectManager $manager): void
    {
        $fixtureLoader = $this->fixtureLoader ?? throw new \RuntimeException('FixtureLoader not set.');
        $osDefinitions = $fixtureLoader->load('os.yml');
        $editorialDefinitions = $fixtureLoader->load('editorial.yml');

        $faker = Factory::create();
        $now = time();

        foreach (array_merge($osDefinitions, $editorialDefinitions) as $fixture) {
            $content = new Content();

            $content->setNid($fixture['nid'] ?? mt_rand());
            $content->setAgency($fixture['agency'] ?? mt_rand(100000, 999999));
            $content->setType($fixture['type'] ?? $faker->word);

            foreach ($fixture['fields'] as $field => &$values) {
                if (is_null($values['value'])) {
                    switch ($field) {
                        case 'title':
                            $value = $faker->sentence;
                            break;
                        case 'publish_date':
                            $value = gmdate('c', $now - mt_rand(1, 5) * 86400);
                            break;
                        default:
                            $value = null;
                    }

                    $values['value'] = $value;
                }
            }

            $content->setFields($fixture['fields'] ?? []);
            $content->setTaxonomy($fixture['taxonomy'] ?? []);
            $content->setList($fixture['list'] ?? []);

            $manager->persist($content);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AgencyFixtures::class,
        ];
    }
}
