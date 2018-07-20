<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use Symfony\Component\HttpFoundation\Response;

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
            'content_type' => 'os',
        ];

        $uri = implode(
            '/',
            [
                self::URI,
                $parameters['content_type'],
            ]
        );

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

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
            'content_type' => 'os',
        ];

        $uri = implode(
            '/',
            [
                self::URI,
                $parameters['content_type'],
            ]
        );

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

        $result = $this->assertResponse($response);
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
        $this->assertCount(count($vocabularies), $result['items']);
        $this->assertArraySubset($vocabularies, array_keys($result['items']));
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
