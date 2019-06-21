<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\MenuFixtures;

/**
 * Class MenuFetchTest
 *
 * Functional tests for fetching menu related entries.
 */
class MenuFetchTest extends AbstractFixtureAwareTest implements AssertItemStructureInterface
{
    use AssertResponseStructureTrait;

    const URI = '/menu/fetch';

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
        $this->assertEquals(0, $result['hits']);
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
        $this->assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals(self::AGENCY, $item['agency']);
            $this->assertItemStructure($item);
        }

        $this->assertGreaterThan(0, $result['hits']);
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

        $menuIds = [];
        // Fetch items till empty result set.
        while (true) {
            /** @var Response $response */
            $response = $this->request(self::URI, $parameters, 'GET');

            $result = $this->assertResponse($response);

            if (empty($result['items'])) {
                break;
            }

            $this->assertLessThanOrEqual($amount, count($result['items']));
            $this->assertGreaterThan(0, $result['hits']);

            foreach ($result['items'] as $item) {
                // Node id's normally should not repeat for same agency.
                $this->assertNotContains($item['mlid'], $menuIds);
                $this->assertEquals(self::AGENCY, $item['agency']);
                $menuIds[] = $item['mlid'];
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
        $totalMenuCount = count($result['items']);

        $this->assertCount($totalMenuCount, $menuIds);
    }

    /**
     * {@inheritdoc}
     */
    public function assertItemStructure(array $item)
    {
        $this->assertArrayHasKey('mlid', $item);
        $this->assertArrayHasKey('agency', $item);
        $this->assertArrayHasKey('type', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('url', $item);
        $this->assertArrayHasKey('weight', $item);
        $this->assertArrayHasKey('enabled', $item);
    }

    /**
     * {@inheritdoc}
     */
    public function getFixtures()
    {
        return [
            new AgencyFixtures(),
            new MenuFixtures(),
        ];
    }
}
