<?php

namespace App\Tests;

use App\DataFixtures\MongoDB\AgencyFixtures;
use App\DataFixtures\MongoDB\ContentFixtures;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContentSearchTest
 *
 * Functional tests for searching content related entries.
 *
 * TODO: Should be adapted to new search controller.
 *
 * @see \AppBundle\Controller\RestController::searchExtendedAction()
 */
class ContentSearchExtendedTest extends AbstractFixtureAwareTest implements AssertItemStructureInterface
{
    use AssertResponseStructureTrait;

    const URI = '/content/search-extended';

    /**
     * Fetch with wrong key.
     */
    public function testSearchWithWrongKey()
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
        $this->assertArrayHasKey('hits', $result);
        $this->assertEquals(0, $result['hits']);
    }

    /**
     * Search without all parameters.
     */
    public function testSearchWithMissingParameters()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'q' => '',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertFalse($result['status']);
        $this->assertCount(0, $result['items']);
        $this->assertArrayHasKey('hits', $result);
        $this->assertEquals(0, $result['hits']);
    }

    /**
     * Search by type.
     */
    public function testTypeSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'q' => '("type[eq]:os")',
            'format' => 'full',
            'amount' => 100,
        ];

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertGreaterThan(10, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertEquals('os', $item['type']);
        }

        $this->assertArrayHasKey('hits', $result);
        $this->assertGreaterThan(0, $result['hits']);
    }

    /**
     * Search by keyword.
     */
    public function testPartialSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'q' => '("fields.title.value[regex]:adv")',
            'format' => 'full',
            'amount' => 100,
        ];

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertContains('adv', strtolower($item['fields']['title']['value']));
        }

        $this->assertArrayHasKey('hits', $result);
        $this->assertGreaterThan(0, $result['hits']);
    }

    /**
     * Search using OR operator.
     */
    public function testOrSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'q' => '("type[eq]:os") OR ("type[eq]:editorial")',
            'format' => 'full',
            'amount' => 100,
        ];

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertContains($item['type'], ['os', 'editorial']);
        }

        $this->assertArrayHasKey('hits', $result);
        $this->assertGreaterThan(0, $result['hits']);
    }

    /**
     * Search using AND operator.
     */
    public function testAndSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'q' => '("type[eq]:os") AND ("fields.title.value[regex]:fear")',
            'format' => 'full',
            'amount' => 100,
        ];

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertContains('os', $item['type']);
            $this->assertContains('fear', strtolower($item['fields']['title']['value']));
        }

        $this->assertArrayHasKey('hits', $result);
        $this->assertGreaterThan(0, $result['hits']);
    }

    /**
     * Search with an 'and' in query.
     */
    public function testAndAsSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'q' => '("fields.title.value[regex]:fear and desire")',
            'format' => 'full',
            'amount' => 100,
        ];

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertContains('fear and desire', strtolower($item['fields']['title']['value']));
        }

        $this->assertArrayHasKey('hits', $result);
        $this->assertGreaterThan(0, $result['hits']);
    }

    /**
     * Search result with default format.
     */
    public function testDefaultResultFormat()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'q' => '("agency[eq]:999999")',
            'amount' => 100,
        ];

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);
        }

        $this->assertArrayHasKey('hits', $result);
        $this->assertGreaterThan(0, $result['hits']);
    }

    /**
     * Search result with short format.
     */
    public function testShortResultFormat()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'q' => '("agency[eq]:999999") AND ("fields.title.value[regex]:fear")',
            'format' => 'short'
        ];

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertContains('fear', strtolower($item));
        }

        $this->assertArrayHasKey('hits', $result);
        $this->assertGreaterThan(0, $result['hits']);
    }

    /**
     * Search result with full format.
     */
    public function testFullResultFormat()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'q' => '("agency[eq]:999999") AND ("fields.title.value[regex]:fear")',
            'format' => 'full'
        ];

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('fields', $item);
            $this->assertArrayHasKey('title', $item['fields']);
            $this->assertArrayHasKey('value', $item['fields']['title']);
            $this->assertArrayHasKey('taxonomy', $item);
            $this->assertArrayHasKey('list', $item);
        }

        $this->assertArrayHasKey('hits', $result);
        $this->assertGreaterThan(0, $result['hits']);
    }

    /**
     * {@inheritdoc}
     */
    public function assertItemStructure(array $item)
    {
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('nid', $item);
        $this->assertArrayHasKey('agency', $item);
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
