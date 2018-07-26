<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContentSearchTest
 *
 * Functional tests for searching content related entries.
 */
class ContentSearchTest extends AbstractFixtureAwareTest implements AssertItemStructureInterface
{
    use AssertResponseStructureTrait;

    const URI = '/content/search';

    /**
     * Fetch with wrong key.
     */
    public function testSearchWithWrongKey()
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
     * Search without all parameters.
     */
    public function testSearchWithMissingParameters()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
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
        $type = 'os';
        $field = 'type';
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'field' => [$field],
            'query' => [$type],
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertCount(10, $result['items']);

        // Collect result node id's and check every item for type equality within one api request.
        $ids = array_map(function ($v) {
            return $v['nid'];
        }, $result['items']);

        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'node' => implode(',', $ids),
        ];
        $response = $this->request('/content/fetch', $parameters, 'GET');
        $result = $this->assertResponse($response);

        foreach ($result['items'] as $item) {
            $this->assertEquals($type, $item[$field]);
        }
    }

    /**
     * Search by partial query.
     */
    public function testPartialSearch()
    {
        $field = 'type';
        $query = 'edito';   // Stands for 'editorial'.
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'field' => [$field],
            'query' => [$query],
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);

        // Collect result node id's and check every item for type value to contain
        // 'edito' substring in their type string. This would match 'editorial' node types.
        $ids = array_map(function ($v) {
            return $v['nid'];
        }, $result['items']);

        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'node' => implode(',', $ids),
        ];
        $response = $this->request('/content/fetch', $parameters, 'GET');
        $result = $this->assertResponse($response);

        $this->assertCount(10, $result['items']);

        foreach ($result['items'] as $item) {
            $position = strpos($item['type'], $query);
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
            'key' => self::KEY,
            'field' => ['fields.title.value'],
            'query' => ['^[a-z]'],
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);
            $this->assertEquals(1, preg_match('/'.$parameters['query'][0].'/i', $item['title']));
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
            'key' => self::KEY,
            'field' => ['type'],
            'query' => ['os'],
            'amount' => $amount,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertResponseStructure($result);
        $this->assertCount($amount, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);
        }
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
            'key' => self::KEY,
            'field' => ['type'],
            'query' => ['editorial|os'],    // This would query items of both 'editorial' and 'os' types.
            'amount' => $amount,
            'skip' => $skip,
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
                $this->assertItemStructure($item);
                // Node id's normally should not repeat for same agency.
                $this->assertNotContains($item['nid'], $results);
                $results[] = $item['nid'];
            }

            $skip += $amount;
            $parameters['skip'] = $skip;
        }

        $this->assertCount(22, $results);
        // Expect zero, since we reached end of the list.
        $this->assertCount(0, $result['items']);
    }

    /**
     * Fetch search results filtered by taxonomy terms.
     */
    public function testTaxonomySearch()
    {
        $query = 'Spillefilm,Dokumentar';
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'query' => [$query],
            'field' => ['taxonomy.field_category.terms'],
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        // Collect result node id's, fetch these within one call
        // and check every item for term(s) existence.
        $ids = array_map(function ($v) {
            return $v['nid'];
        }, $result['items']);

        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'node' => implode(',', $ids),
        ];
        $response = $this->request('/content/fetch', $parameters, 'GET');
        $result = $this->assertResponse($response);

        foreach ($result['items'] as $item) {
            // No idea why assertArraySubset() fails here.
            $this->assertNotEmpty(
                array_intersect(
                    $item['taxonomy']['field_category']['terms'],
                    explode(',', $query)
                )
            );
        }
    }

    /**
     * Fetch search result matching complex criteria.
     */
    public function testComplexSearch()
    {
        $query = ['os', 'Hjemmefra'];
        $field = ['type', 'taxonomy.field_realm.terms'];
        $amount = 2;
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'query' => $query,
            'field' => $field,
            'amount' => $amount,
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);
        $this->assertNotEmpty($result['items']);
        $this->assertCount($amount, $result['items']);

        // Collect result node id's, fetch these within one call
        // and check every item for previous criteria search match.
        $ids = array_map(function ($v) {
            return $v['nid'];
        }, $result['items']);

        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'node' => implode(',', $ids),
        ];
        $response = $this->request('/content/fetch', $parameters, 'GET');
        $result = $this->assertResponse($response);

        foreach ($result['items'] as $item) {
            $this->assertContains($query[0], $item[$field[0]]);
            $this->assertContains($query[1], $item['taxonomy']['field_realm']['terms']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assertItemStructure(array $item)
    {
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('nid', $item);
        $this->assertArrayHasKey('title', $item);
        $this->assertArrayHasKey('changed', $item);
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
