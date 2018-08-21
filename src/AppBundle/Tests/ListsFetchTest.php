<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ListsFixtures;

/**
 * Class ListsFetchTest
 *
 * Functional test for fetching list related entries.
 */
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
            'agency' => self::AGENCY,
            'key' => self::KEY.'-wrong',
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
            'agency' => self::AGENCY,
            'key' => self::KEY,
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals(self::AGENCY, $item['agency']);
            $this->assertTrue($item['promoted']);
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
            'agency' => self::AGENCY,
            'key' => self::KEY,
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
     * Promoted value filter fetch.
     */
    public function testFetchPromoted()
    {
        // Fetch promoted only.
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'promoted' => 1,
            'amount' => 99,
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $promotedCount = count($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertTrue($item['promoted']);
        }

        // Fetch not promoted only.
        $parameters['promoted'] = 0;
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $notPromotedCount = count($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertFalse($item['promoted']);
        }

        // Fetch all.
        $parameters['promoted'] = -1;
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $allCount = count($result['items']);

        // Expect promoted count and not promoted count to match total count.
        $this->assertEquals($allCount, $promotedCount + $notPromotedCount);
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
