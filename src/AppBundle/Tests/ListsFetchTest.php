<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ListsFixtures;

class ListsFetchTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const URI = '/list/fetch';

    /**
     * Fetch with wrong key.
     */
    public function testFetchWithWrongKey()
    {
        $parameters = [
            'agency' => SELF::AGENCY,
            'key' => SELF::KEY.'-wrong',
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);
        $this->assertFalse($result['status']);
        $this->assertEmpty($result['items']);
        $this->assertEquals($result['message'], 'Failed validating request. Check your credentials (agency & key).');
    }

    /**
     * Default fetch.
     */
    public function testFetchDefault()
    {
        $parameters = [
            'agency' => SELF::AGENCY,
            'key' => SELF::KEY,
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals(self::AGENCY, $item['agency']);
        }
    }

    /**
     * Paged fetch.
     */
    public function testFetchWithPager()
    {
        $skip = 0;
        $amount = 2;
        $parameters = [
            'agency' => SELF::AGENCY,
            'key' => SELF::KEY,
            'amount' => $amount,
            'skip' => $skip,
        ];

        $list_ids = [];
        // Fetch items till empty result set.
        while (true) {
            /** @var Response $response */
            $response = $this->request(self::URI, $parameters, 'GET');

            $result = $this->assertResponse($response);

            if (empty($result['items'])) {
                break;
            }

            $this->assertLessThanOrEqual($amount, count($result['items']));

            foreach ($result['items'] as $item) {
                // Node id's normally should not repeat for same agency.
                $this->assertNotContains($item['key'], $list_ids);
                $this->assertEquals(self::AGENCY, $item['agency']);
                $list_ids[] = $item['key'];
            }

            $skip += $amount;
            $parameters['skip'] = $skip;
        }
        // Expect zero, since we reached end of the list.
        $this->assertCount(0, $result['items']);

        $parameters['skip'] = 0;
        $parameters['amount'] = 999;

        $response = $this->request(self::URI, $parameters, 'GET');
        $result = $this->assertResponse($response);
        $totalListCount = count($result['items']);

        $this->assertCount($totalListCount, $list_ids);
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
