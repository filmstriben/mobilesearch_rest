<?php

namespace App\Tests;

use App\DataFixtures\MongoDB\AgencyFixtures;
use App\DataFixtures\MongoDB\ContentFixtures;

/**
 * Class VocabulariesTest
 *
 * Functional tests for fetching taxonomy related vocabularies.
 */
class VocabulariesTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const URI = '/taxonomy/vocabularies';

    /**
     * Fetch term suggestions with missing agency.
     */
    public function testFetchWithMissingAgency()
    {
        $parameters = [
            'agency' => '',
            'key' => self::KEY,
            'contentType' => 'os',
        ];

        $uri = implode(
            '/',
            [
                self::URI,
                $parameters['contentType'],
            ]
        );

        $response = $this->request($uri, $parameters, 'GET');

        $result = $this->assertResponse($response);
        $this->assertFalse($result['status']);
        $this->assertEmpty($result['items']);

        // Test new endpoint.
        // TODO: Previous assertions to be removed after deprecated route is removed.
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);
        $this->assertFalse($result['status']);
        $this->assertEmpty($result['items']);
    }

    /**
     * Fetches vocabularies for a certain content type.
     */
    public function testFetchVocabularies()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
            'contentType' => 'os',
        ];

        $uri = implode(
            '/',
            [
                self::URI,
                $parameters['contentType'],
            ]
        );

        $vocabularies = [
            'field_category',
            'field_realm',
            'field_country',
            'field_production',
            'field_genre',
            'field_language',
            'field_subject',
            'field_subtitles',
            'act',
            'aus',
            'cng',
            'drt',
            'ant',
            'cre',
        ];

        $response = $this->request($uri, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertCount(count($vocabularies), $result['items']);
        $matches = array_intersect(array_keys($result['items']), $vocabularies);
        $this->assertEquals(count($matches), count($vocabularies));

        // Test new endpoint.
        // TODO: Previous assertions to be removed after deprecated route is removed.
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertCount(count($vocabularies), $result['items']);
        $matches = array_intersect(array_keys($result['items']), $vocabularies);
        $this->assertEquals(count($matches), count($vocabularies));
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
