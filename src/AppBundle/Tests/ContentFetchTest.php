<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use AppBundle\Rest\RestContentRequest;
use Symfony\Component\HttpFoundation\Response;

class ContentFetchTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const URI = '/content/fetch';

    /**
     * Fetch with wrong key.
     */
    public function testFetchWithWrongKey()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY.'-wrong',
        ];
        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertFalse($result['status']);
        $this->assertEmpty($result['items']);
        $this->assertEquals($result['message'], 'Failed validating request. Check your credentials (agency & key).');
    }

    /**
     * Fetch with missing data.
     */
    public function testFetchWithEmptyAgency()
    {
        $parameters = [
            'agency' => '',
            'key' => self::KEY,
        ];
        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertFalse($result['status']);
        $this->assertEmpty($result['items']);
        $this->assertEquals($result['message'], 'Failed validating request. Check your credentials (agency & key).');
    }

    /**
     * Fetch by nid.
     */
    public function testFetchByNid()
    {
        $nid = 1000;
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'node' => $nid,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertCount(1, $result['items']);
        $this->assertEquals($nid, $result['items'][0]['nid']);
        $this->assertEquals(self::AGENCY, $result['items'][0]['agency']);
    }

    /**
     * Fetch by multiple nid's.
     */
    public function testFetchByMultipleNid()
    {
        $nids = array_merge(
            range(mt_rand(1000, 1005), mt_rand(1006, 1010)),    // os nodes
            range(mt_rand(2000, 2005), mt_rand(2006, 2010))     // editorial nodes
        );
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'node' => implode(',', $nids),
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        foreach ($result['items'] as $item) {
            $this->assertContains($item['nid'], $nids);
            $this->assertEquals(self::AGENCY, $item['agency']);
        }
    }

    /**
     * Fetch by type.
     */
    public function testFetchByType()
    {
        $type = 'editorial';
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'type' => $type,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals($type, $item['type']);
            $this->assertEquals(self::AGENCY, $item['agency']);
        }
    }

    /**
     * Default fetch.
     */
    public function testFetchAll()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        // 10 items are returned by default.
        $this->assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals(self::AGENCY, $item['agency']);
        }
    }

    /**
     * Limited fetch.
     */
    public function testFetchWithSmallAmount()
    {
        $amount = 2;
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'amount' => $amount,
            'type' => 'os',
        ];
        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertCount($amount, $result['items']);
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
            'status' => RestContentRequest::STATUS_ALL,
        ];

        $node_ids = [];
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
                $this->assertNotContains($item['nid'], $node_ids);
                $this->assertEquals(self::AGENCY, $item['agency']);
                $node_ids[] = $item['nid'];
            }

            $skip += $amount;
            $parameters['skip'] = $skip;
        }

        $this->assertCount(22, $node_ids);
        // Expect zero, since we reached end of the list.
        $this->assertCount(0, $result['items']);
    }

    /**
     * Fetch sorted.
     */
    public function testFetchWithSorting()
    {
        $sort = 'nid';
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'sort' => $sort,
            'order' => 'ASC',
        ];

        // Ascending sort.
        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $this->assertGreaterThan($result['items'][$i - 1][$sort], $result['items'][$i][$sort]);
        }

        // Descending sort.
        $parameters['order'] = 'DESC';

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $this->assertLessThan($result['items'][$i - 1][$sort], $result['items'][$i][$sort]);
        }
    }

    /**
     * Fetch sorted by complex field.
     */
    public function testFetchWithNestedFieldSorting()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'sort' => 'fields.title.value',
            'order' => 'ASC',
            'type' => 'os',
        ];

        // Ascending order.
        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $first_node = $result['items'][$i];
            $second_node = $result['items'][$i - 1];
            $comparison = strcmp($first_node['fields']['title']['value'], $second_node['fields']['title']['value']);
            $this->assertGreaterThan(0, $comparison);
        }

        // Descending order;
        $parameters['order'] = 'DESC';

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $first_node = $result['items'][$i];
            $second_node = $result['items'][$i - 1];
            $comparison = strcmp($first_node['fields']['title']['value'], $second_node['fields']['title']['value']);
            $this->assertLessThan(0, $comparison);
        }
    }

    /**
     * Fetch by complex filtering.
     */
    public function testFetchComplex()
    {
        $type = 'os';
        $amount = 2;
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'type' => $type,
            'amount' => $amount,
            'skip' => 1,
            'sort' => 'fields.title.value',
            'order' => 'DESC',
            'status' => RestContentRequest::STATUS_ALL,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertCount($amount, $result['items']);

        // Check some static values.
        foreach ($result['items'] as $item) {
            $this->assertEquals(self::AGENCY, $item['agency']);
            $this->assertEquals($type, $item['type']);
        }

        // Check order.
        for ($i = 1; $i < count($result['items']); $i++) {
            $first_node = $result['items'][$i];
            $second_node = $result['items'][$i - 1];
            $comparison = strcmp($first_node['fields']['title']['value'], $second_node['fields']['title']['value']);
            $this->assertLessThan(0, $comparison);
        }
    }

    /**
     * Fetches default set of published content.
     */
    public function testFetchByDefaultStatus()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'type' => 'os',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $status = $item['fields']['status']['value'];
            $this->assertContains(
                $status,
                [
                    RestContentRequest::STATUS_PUBLISHED,
                    RestContentRequest::STATUS_UNPUBLISHED,
                ]
            );
        }
    }

    /**
     * Fetches content filtered by status.
     */
    public function testFetchByStatus()
    {
        // Fetch published content.
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'status' => RestContentRequest::STATUS_PUBLISHED,
            'type' => 'os',
            'amount' => 10,
            'skip' => 0,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        $publishedCount = count($result['items']);

        foreach ($result['items'] as $item) {
            $status = $item['fields']['status']['value'];
            $this->assertEquals($parameters['status'], $status);
        }

        // Fetch unpublished content.
        $parameters['status'] = RestContentRequest::STATUS_UNPUBLISHED;

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        $unpublishedCount = count($result['items']);

        foreach ($result['items'] as $item) {
            $status = $item['fields']['status']['value'];
            $this->assertEquals($parameters['status'], $status);
        }

        // Fetch all content.
        $parameters['status'] = RestContentRequest::STATUS_ALL;
        $parameters['amount'] = 999;

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertCount($publishedCount + $unpublishedCount, $result['items']);
    }

    /**
     * {@inheritdoc}
     */
    public function getFixtures()
    {
        return [
            new AgencyFixtures(),
            new ContentFixtures(),
        ];
    }
}
