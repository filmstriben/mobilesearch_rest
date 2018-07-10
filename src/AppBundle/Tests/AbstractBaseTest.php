<?php

namespace AppBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBaseTest extends WebTestCase
{
    private $client;

    private $container;

    /**
     * Returns the http client.
     *
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the DI container.
     *
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Asserts service response structure.
     *
     * @param array $response   Decoded response.
     */
    abstract public function assertResponseStructure(array $response);

    /**
     * Sends a request.
     *
     * @param string $uri           URI target to send request.
     * @param array $parameters     Request parameters.
     * @param string $method        Request method.
     *
     * @return Response
     */
    public function request($uri, array $parameters, $method = 'GET')
    {
        $client = $this->getClient();
        $client->request($method, $uri, $parameters);

        /** @var Response $response */
        $response = $client->getResponse();

        return $response;
    }

    /**
     * Asserts and decodes service responses.
     *
     * @param Response $response    Response object.
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
     * Asserts an ISO-8601 date string.
     *
     * @param string $date  Input date.
     */
    public function assertIsoDate($date)
    {
        $nodeChangedDateValue = strtotime($date);
        $this->assertInternalType('int', $nodeChangedDateValue);
        $this->assertEquals(gmdate('c', $nodeChangedDateValue), $date);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->container = $this->client->getContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
    }
}
