<?php

namespace App\Tests;

use App\DataFixtures\MongoDB\AgencyFixtures;
use App\DataFixtures\MongoDB\ConfigurationFixtures;

/**
 * Class ConfigurationTest.
 */
class ConfigurationTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const URI = '/configuration';

    /**
     * Default fetch of configuration items.
     */
    public function testConfigurationFetch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);
        $this->assertNotEmpty($result['items']);
        // 1 item is provided in the fixture, yet.
        $this->assertCount(1, $result['items']);
    }

    /**
     * Checks delete of a config item.
     */
    public function testConfigurationDelete()
    {
        $parameters = [
            'credentials' => [
                'agencyId' => self::AGENCY,
                'key' => self::KEY,
            ],
            'body' => [
                'agency' => self::AGENCY,
            ],
        ];

        $response = $this->request(self::URI, $parameters, 'DELETE');

        $result = $this->assertResponse($response);
        $this->assertTrue($result['status']);

        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);
        $this->assertEmpty($result['items']);
    }

    /**
     * Checks creation of a new config item.
     */
    public function testConfigurationCreate()
    {
        $parameters = [
            'credentials' => [
                'agencyId' => self::AGENCY,
                'key' => self::KEY,
            ],
            'body' => [
                'agency' => '999998',
                'settings' => [],
            ],
        ];

        $response = $this->request(self::URI, $parameters, 'PUT');

        $result = $this->assertResponse($response);
        $this->assertTrue($result['status']);

        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);
        $this->assertNotEmpty($result['items']);
        // 1 item is provided in the fixture, yet.
        $this->assertCount(2, $result['items']);
        $this->assertArrayHasKey('999998', $result['items']);
    }

    /**
     * Checks updates on a config item.
     */
    public function testConfigurationUpdate()
    {
        $parameters = [
            'credentials' => [
                'agencyId' => self::AGENCY,
                'key' => self::KEY,
            ],
            'body' => [
                'agency' => '999999',
                'settings' => [
                    'xtra_setting' => 'alpha',
                ],
            ],
        ];

        $response = $this->request(self::URI, $parameters, 'POST');

        $result = $this->assertResponse($response);
        $this->assertTrue($result['status']);

        $parameters = [
            'agency' => self::AGENCY,
            'key' => self::KEY,
        ];

        $response = $this->request(self::URI, $parameters, 'GET');

        $result = $this->assertResponse($response);
        $this->assertArrayHasKey('999999', $result['items']);
        $this->assertArrayHasKey('xtra_setting', $result['items']['999999']);
        $this->assertEquals($result['items']['999999']['xtra_setting'], 'alpha');
    }

    /**
     * Checks creation of config item on a faulty agency.
     */
    public function testConfigurationWrongChildCreate()
    {
        $parameters = [
            'credentials' => [
                'agencyId' => self::AGENCY,
                'key' => self::KEY,
            ],
            'body' => [
                'agency' => '999977',
                'settings' => [],
            ],
        ];

        $response = $this->request(self::URI, $parameters, 'PUT');

        $result = $this->assertResponse($response);
        $this->assertFalse($result['status']);
    }

    /**
     * Checks updates of config item on a faulty agency.
     */
    public function testConfigurationWrongChildUpdate()
    {
        $parameters = [
            'credentials' => [
                'agencyId' => self::AGENCY,
                'key' => self::KEY,
            ],
            'body' => [
                'agency' => '999977',
                'settings' => [
                    'xtra_setting' => 'alpha'
                ],
            ],
        ];

        $response = $this->request(self::URI, $parameters, 'POST');

        $result = $this->assertResponse($response);
        $this->assertFalse($result['status']);
    }

    /**
     * Checks deletion of config item on a faulty config item.
     */
    public function testConfigurationWrongChildDelete()
    {
        $parameters = [
            'credentials' => [
                'agencyId' => self::AGENCY,
                'key' => self::KEY,
            ],
            'body' => [
                'agency' => '999977',
            ],
        ];

        $response = $this->request(self::URI, $parameters, 'POST');

        $result = $this->assertResponse($response);
        $this->assertFalse($result['status']);
    }

    /**
     * Check fetch of config item without a specific agency.
     */
    public function testConfigurationEmptyAgency()
    {
        $parameters = [
            'credentials' => [
                'agencyId' => self::AGENCY,
                'key' => self::KEY,
            ],
            'body' => [
                'agency' => null,
                'settings' => [
                    'xtra_setting' => 'alpha'
                ],
            ],
        ];

        $response = $this->request(self::URI, $parameters, 'POST');

        $result = $this->assertResponse($response);
        $this->assertFalse($result['status']);
    }

    /**
     * {@inheritDoc}
     */
    public function getFixtures()
    {
        return [
            new AgencyFixtures(),
            new ConfigurationFixtures(),
        ];
    }
}
