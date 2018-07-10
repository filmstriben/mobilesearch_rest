<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use AppBundle\Rest\RestContentRequest;
use Symfony\Component\HttpFoundation\Response;

class ContentSearchTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const AGENCY = '999999';
    const URI = '/content/search';

    /**
     * Search without all parameters.
     */
    public function testMissingParameters()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => '',
            'query' => '',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertFalse($result['status']);
        $this->assertCount(0, $result['items']);
    }

    /**
     * Search by type.
     */
    public function testTypeSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_news',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);
            $this->assertEquals($parameters['query'], $item[$parameters['field']]);
        }
    }

    /**
     * Search by partial query.
     */
    public function testPartialSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);

            $position = strpos($item['type'], $parameters['query']);
            $this->assertGreaterThanOrEqual(0, $position);
            $this->assertNotFalse($position);
        }
    }

    /**
     * Search by regex.
     */
    public function testRegexSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'fields.title.value',
            'query' => '^[a-z]',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);
            $this->assertEquals(1, preg_match('/'.$parameters['query'].'/i', $item['title']));
        }
    }

    /**
     * Fetch limited results.
     */
    public function testLimitedSearch()
    {
        $amount = 3;
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_',
            'amount' => $amount,
            'status' => RestContentRequest::STATUS_ALL,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertResponseStructure($result);
        $this->assertTrue($result['status']);
        $this->assertCount($amount, $result['items']);
    }

    /**
     * Fetches paged search results.
     */
    public function testPagedSearch()
    {
        $amount = 2;
        $skip = 0;
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_',
            'amount' => $amount,
            'skip' => $skip,
            'status' => RestContentRequest::STATUS_ALL,
        ];

        $results = [];

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
                $this->assertNotContains($item['nid'], $results);
                $results[] = $item['nid'];
            }

            $skip += $amount;
            $parameters['skip'] = $skip;
        }

        $this->assertCount(7, $results);
        // Expect zero, since we reached end of the list.
        $this->assertEquals(0, count($result['items']));
    }

    /**
     * Fetches nodes filtered by status.
     */
    public function testStatusFilterSearch()
    {
        // Fetch published nodes.
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_',
            'status' => RestContentRequest::STATUS_PUBLISHED,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        foreach ($result['items'] as $item) {
            $this->assertEquals($parameters['status'], $item['status']);
        }
        $publishedCount = count($result['items']);

        // Fetch unpublished nodes.
        $parameters['status'] = RestContentRequest::STATUS_UNPUBLISHED;

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        foreach ($result['items'] as $item) {
            $this->assertEquals($parameters['status'], $item['status']);
        }
        $unpublishedCount = count($result['items']);

        // Fetch all nodes.
        $parameters['status'] = RestContentRequest::STATUS_ALL;

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $allCount = count($result['items']);

        // Assume that a sum of published and unpublished nodes is the correct
        // number of nodes that exist.
        $this->assertEquals($allCount, $unpublishedCount + $publishedCount);
    }

    /**
     * Fetches upcoming events.
     */
    public function testUpcomingEventsSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_event',
            'upcoming' => 1,
            'status' => RestContentRequest::STATUS_ALL,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $event_unixtime = strtotime($item['event_date']['from']);
            $this->assertNotEquals(-1, $event_unixtime);
            $this->assertGreaterThan(time(), $event_unixtime);
        }
    }

    /**
     * Fetches default set of published content.
     */
    public function testDefaultStatusSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_news',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals(RestContentRequest::STATUS_PUBLISHED, $item['status']);
        }
    }

    /**
     * Asserts item structure in the response.
     *
     * @param array $item   One item from the result set.
     */
    private function assertItemStructure(array $item)
    {
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('nid', $item);
        $this->assertArrayHasKey('title', $item);
        $this->assertArrayHasKey('changed', $item);
        $this->assertArrayHasKey('type', $item);
        // Attempt to parse a meaningful date format, also it has to be in ISO-8601 format.
        $this->assertIsoDate($item['changed']);

        // Events have date in response.
        if ('ding_event' == $item['type']) {
            $this->assertArrayHasKey('event_date', $item);
            $this->assertArrayHasKey('from', $item['event_date']);
            $this->assertIsoDate($item['event_date']['from']);
            $this->assertArrayHasKey('to', $item['event_date']);
            $this->assertIsoDate($item['event_date']['to']);
            $this->assertArrayHasKey('all_day', $item['event_date']);
            $this->assertInternalType('boolean', $item['event_date']['all_day']);
        }
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
