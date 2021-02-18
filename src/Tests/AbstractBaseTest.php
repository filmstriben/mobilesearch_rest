<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AbstractBaseTest
 *
 * Base class for functional tests.
 */
abstract class AbstractBaseTest extends WebTestCase
{
    protected $httpClient;

    /**
     * Returns the DI container.
     *
     * @return mixed
     */
    public function getContainer()
    {
        return static::$container;
    }

    /**
     * Asserts service response structure.
     *
     * @param array $response Decoded response.
     */
    abstract public function assertResponseStructure(array $response);

    /**
     * Sends a request.
     *
     * @param string $uri       URI target to send request.
     * @param array $parameters Request parameters.
     * @param string $method    Request method.
     *
     * @return Response
     */
    public function request($uri, array $parameters, $method = 'GET')
    {
        if ('GET' !== $method) {
            $this->httpClient->request($method, $uri, [], [], ['Content-Type' => 'application/json'], json_encode($parameters));
        } else {
            $this->httpClient->request($method, $uri, $parameters);
        }

        return $this->httpClient->getResponse();
    }

    /**
     * Asserts and decodes service responses.
     *
     * @param Response $response Response object.
     *
     * @return mixed                Response array, false on failure.
     */
    public function assertResponse(Response $response)
    {
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = static::createClient();

        static::bootKernel();
    }
}
