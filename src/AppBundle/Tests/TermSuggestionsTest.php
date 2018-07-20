<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use Symfony\Component\HttpFoundation\Response;

class TermSuggestionsTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const URI = '/taxonomy/terms';

    /**
     * Fetch term suggestions with missing agency.
     */
    public function testMissingAgency()
    {
        $parameters = [
            'agency' => '',
            'key' => self::KEY,
            'vocabulary' => 'field_realm',
            'content_type' => 'os',
            'query' => 'Hjemmefra',
        ];

        $uri = implode(
            '/',
            [
                self::URI,
                $parameters['vocabulary'],
                $parameters['content_type'],
                $parameters['query'],
            ]
        );

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

        $result = $this->assertResponse($response);

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
            'key' => self::KEY,
            'vocabulary' => 'field_genre',
            'content_type' => 'os',
            'query' => 'drama',
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
            'key' => self::KEY,
            'vocabulary' => 'drt',
            'content_type' => 'os',
            'query' => 'Ronnie',
        ];

        $uri = implode(
            '/',
            [
                self::URI,
                $parameters['vocabulary'],
                $parameters['content_type'],
                $parameters['query'],
            ]
        );

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $terms = $result['items'];
        $this->assertNotEmpty($terms);

        foreach ($terms as $term) {
            $this->assertContains($parameters['query'], $term, '', true);
        }
    }

    /**
     * Wrapper method to check suggested term existence.
     *
     * @param array $parameters Query parameters.
     */
    private function assertTermExistence(array $parameters)
    {
        $uri = implode(
            '/',
            [
                self::URI,
                $parameters['vocabulary'],
                $parameters['content_type'],
                $parameters['query'],
            ]
        );

        /** @var Response $response */
        $response = $this->request($uri, $parameters, 'GET');

        $result = $this->assertResponse($response);

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
