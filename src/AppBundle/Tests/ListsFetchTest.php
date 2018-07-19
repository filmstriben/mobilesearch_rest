<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ListsFixtures;

class ListsFetchTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const URI = '/list/fetch';

    public function testTest()
    {
        $this->assertTrue(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getFixtures()
    {
        return [
            new AgencyFixtures(),
            new ListsFixtures(),
        ];
    }
}
