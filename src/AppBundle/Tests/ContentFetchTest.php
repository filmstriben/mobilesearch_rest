<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use AppBundle\Rest\RestContentRequest;
use Symfony\Component\HttpFoundation\Response;

class ContentFetchTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    public function testFetchWithWrongKey()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY.'-wrong',
        ];
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertFalse($result['status']);
        $this->assertEmpty($result['items']);
        $this->assertEquals($result['message'], 'Failed validating request. Check your credentials (agency & key).');
    }

    /**
     * Fetch with missing data.
     */
    public function testFetchEmptyAgency()
    {
        $parameters = [
            'agency' => '',
            'key' => self::KEY,
        ];
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

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
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertCount(1, $result['items']);
        $this->assertEquals($nid, $result['items'][0]['nid']);
        $this->assertEquals(self::AGENCY, $result['items'][0]['agency']);
    }

    /**
     * Fetch by type.
     */
    public function testFetchByType()
    {
        $type = 'ding_news';
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'type' => $type,
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

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
            'key' => self::KEY
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        // 10 items are returned by default.
        $this->assertLessThan(11, count($result['items']));

        foreach ($result['items'] as $item) {
            $this->assertEquals(self::AGENCY, $item['agency']);
        }
    }

    /**
     * Limited fetch.
     */
    public function testFetchSmallAmount()
    {
        $amount = 2;
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'amount' => $amount,
        ];
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertCount($amount, $result['items']);
    }

    /**
     * Paged fetch.
     */
    public function testPager()
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

        while (true) {
            /** @var Response $response */
            $response = $this->request('/content/fetch', $parameters, 'GET');

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

        $this->assertCount(7, $node_ids);
        // Expect zero, since we reached end of the list.
        $this->assertCount(0, $result['items']);
    }

    /**
     * Fetch sorted.
     */
    public function testSorting()
    {
        $sort = 'nid';
        $order = 'ASC';
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'sort' => $sort,
            'order' => $order,
        ];

        // Ascending sort.
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $this->assertGreaterThan($result['items'][$i - 1][$sort], $result['items'][$i][$sort]);
        }

        // Descending sort.
        $parameters['order'] = 'DESC';

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $this->assertLessThan($result['items'][$i - 1][$sort], $result['items'][$i][$sort]);
        }
    }

    /**
     * Fetch sorted by complex field.
     */
    public function testNestedFieldSorting()
    {
        $sort = 'fields.title.value';
        $order = 'ASC';
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'sort' => $sort,
            'order' => $order,
        ];

        // Ascending order.
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

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
        $response = $this->request('/content/fetch', $parameters, 'GET');

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
        $type = 'ding_news';
        $amount = 2;
        $skip = 1;
        $sort = 'fields.title.value';
        $order = 'DESC';
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'type' => $type,
            'amount' => $amount,
            'skip' => $skip,
            'sort' => $sort,
            'order' => $order,
            'status' => RestContentRequest::STATUS_ALL,
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

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
    public function testDefaultStatus()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $status = $item['fields']['status']['value'];
            $this->assertEquals(RestContentRequest::STATUS_PUBLISHED, $status);
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
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

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
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        $unpublishedCount = count($result['items']);

        foreach ($result['items'] as $item) {
            $status = $item['fields']['status']['value'];
            $this->assertEquals($parameters['status'], $status);
        }

        // Fetch all content.
        $parameters['status'] = RestContentRequest::STATUS_ALL;

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

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
