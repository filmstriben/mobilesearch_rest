<?php

namespace App\Tests;

use App\DataFixtures\MongoDB\AgencyFixtures;
use App\DataFixtures\MongoDB\ContentFixtures;

/**
 * Class TermSuggestionsTest
 *
 * Functional test for fetching taxonomy term suggestions.
 */
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
            'q' => 'Hjemmefra',
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

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
            'contentType' => 'os',
            'q' => 'drama',
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
            'contentType' => 'os',
            'q' => 'Ronnie',
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $terms = $result['items'];
        $this->assertNotEmpty($terms);

        foreach ($terms as $term) {
            $this->assertStringContainsString($parameters['q'], $term, '');
        }
    }

    /**
     * Wrapper method to check suggested term existence.
     *
     * @param array $parameters
     *   Query parameters.
     */
    private function assertTermExistence(array $parameters)
    {
        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);

        $terms = $result['items'];
        $this->assertCount(1, $terms);
        $this->assertTrue($terms[0] === $parameters['q']);
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
