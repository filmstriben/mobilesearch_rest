<?php

namespace App\Tests;

use App\Services\FixtureLoader;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;

abstract class AbstractFixtureAwareTest extends AbstractBaseTest
{
    const AGENCY = '999999';

    const KEY = 'd952bdbc614ae7ef7fbcee661e55f1a462657f53';

    public function setUp(): void
    {
        parent::setUp();

        $odm = static::getContainer()->get('doctrine_mongodb');
        $em = $odm->getManager();

        /** @var FixtureLoader $fixtureLoader */
        $fixtureLoader = static::getContainer()->get(FixtureLoader::class);

        $loader = new Loader();
        foreach ($this->getFixtures() as $fixture) {
            if (method_exists($fixture, 'setFixtureLoader')) {
                $fixture->setFixtureLoader($fixtureLoader);
            }
            $loader->addFixture($fixture);
        }

        $purger = new MongoDBPurger($em);
        $executor = new MongoDBExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

    abstract public function getFixtures();
}
