<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use Symfony\Component\HttpFoundation\Response;

class TermSuggestionsTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const AGENCY = '999999';

    const URI = '/taxonomy/terms';

    /**
     * Fetch term suggestions with missing agency.
     */
    public function testMissingAgency()
    {
        $parameters = [
            'agency' => '',
            'vocabulary' => 'field_ding_event_category',
            'content_type' => 'ding_event',
            'query' => 'Alpha'
        ];

        $uri = implode('/', [
            self::URI,
            $parameters['vocabulary'],
            $parameters['content_type'],
            $parameters['query'],
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
     * Fetches term suggestions.
     */
    public function testTermExistence()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'vocabulary' => 'field_ding_event_category',
            'content_type' => 'ding_event',
            'query' => 'Alpha'
        ];

        $this->assertTermExistence($parameters);
    }

    /**
     * Fetches deeply nested term suggestions.
     */
    public function testNestedTermExistence()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'vocabulary' => 'field_ding_event_category',
            'content_type' => 'ding_event',
            'query' => 'Theta'
        ];

        $this->assertTermExistence($parameters);
    }

    /**
     * Fetches term suggestions.
     */
    public function testTermSuggestions()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'vocabulary' => 'field_ding_event_category',
            'content_type' => 'ding_event',
            'query' => 'a'
        ];

        $uri = implode('/', [
            self::URI,
            $parameters['vocabulary'],
            $parameters['content_type'],
            $parameters['query'],
        ]);

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);

        $terms = $result['items'];

        $this->assertNotEmpty($terms);

        foreach ($terms as $term) {
            $this->assertContains($parameters['query'], $term, '', true);
        }
    }

    /**
     * Wrapper method to check suggested term existence.
     *
     * @param array $parameters     Query parameters.
     */
    private function assertTermExistence(array $parameters)
    {
        $uri = implode('/', [
            self::URI,
            $parameters['vocabulary'],
            $parameters['content_type'],
            $parameters['query'],
        ]);

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);

        $terms = $result['items'];
        $this->assertCount(1, $terms);
        $this->assertTrue($terms[0] === $parameters['query']);
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
