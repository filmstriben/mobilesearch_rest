<?php

namespace App\Tests;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class AbstractFixtureAwareTest
 *
 * Base class for functional tests that rely on fixtures.
 */
abstract class AbstractFixtureAwareTest extends AbstractBaseTest
{
    const AGENCY = '999999';

    const KEY = 'd952bdbc614ae7ef7fbcee661e55f1a462657f53';

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $odm = $this->getContainer()->get('doctrine_mongodb');
        $em = $odm->getManager();

        $loader = new Loader();
        /** @var Fixture $fixture */
        foreach ($this->getFixtures() as $fixture) {
            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer($this->getContainer());
            }
            $loader->addFixture($fixture);
        }

        $purger = new MongoDBPurger($em);
        $executor = new MongoDBExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Provides a list of functions to be loaded prior to test execution.
     *
     * @return array
     */
    abstract public function getFixtures();
}
