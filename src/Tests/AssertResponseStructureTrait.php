<?php

namespace App\Tests;

/**
 * Trait AssertResponseStructureTrait
 *
 * Wraps the API response structure assertions.
 */
trait AssertResponseStructureTrait
{
    /**
     * Asserts data structure of a response.
     *
     * @param array $response
     *   Response array.
     */
    public function assertResponseStructure(array $response)
    {
        $this->assertArrayHasKey('status', $response);
        $this->assertIsBool($response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertIsString($response['message']);
        $this->assertArrayHasKey('items', $response);
        $this->assertIsArray($response['items']);
    }
}
