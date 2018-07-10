<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use Symfony\Component\HttpFoundation\Response;

class VocabulariesTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const AGENCY = '999999';

    const URI = '/taxonomy/vocabularies';

    /**
     * Fetch term suggestions with missing agency.
     */
    public function testMissingAgency()
    {
        $parameters = [
            'agency' => '',
            'content_type' => 'ding_event',
        ];

        $uri = implode('/', [
            self::URI,
            $parameters['content_type'],
        ]);

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);
        $this->assertFalse($result['status']);
        $this->assertEmpty($result['items']);
    }

    /**
     * Fetches vocabularies for a certain content type.
     */
    public function testVocabularies()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'content_type' => 'ding_event',
        ];

        $uri = implode('/', [
            self::URI,
            $parameters['content_type'],
        ]);

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);
        $this->assertCount(1, $result['items']);
        $this->assertArrayHasKey('field_ding_event_category', $result['items']);
        $this->assertNotEmpty($result['items']['field_ding_event_category']);
    }

    /**
     * Fetches empty set of vocabularies.
     */
    public function testEmptyVocabularies()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'content_type' => 'ding_library',
        ];

        $uri = implode('/', [
            self::URI,
            $parameters['content_type'],
        ]);

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);
        $this->assertEmpty($result['items']);
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
