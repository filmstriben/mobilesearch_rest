<?php

namespace AppBundle\Rest;

use AppBundle\Document\Agency;
use AppBundle\Exception\RestException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

/**
 * Class RestBaseRequest
 *
 * Base class for handling POST/PUT/DELETE requests.
 */
abstract class RestBaseRequest
{
    protected $agencyId = null;
    protected $signature = null;
    protected $requestBody = null;
    protected $requiredFields = [];
    protected $em = null;
    protected $primaryIdentifier = '';

    /**
     * Fetch a single entity entry.
     *
     * @param $id     Entity id.
     * @param $agency Agency identifier.
     *
     * @return mixed  Doctrine entity.
     */
    abstract protected function get($id, $agency);

    /**
     * Check whether an entity exists.
     *
     * @param $id     Entity id.
     * @param $agency Agency identifier.
     *
     * @return boolean
     */
    abstract protected function exists($id, $agency);

    /**
     * Persist an entity.
     *
     * @return mixed Doctrine entity.
     */
    abstract protected function insert();

    /**
     * Update an entity.
     *
     * @param $id     Entity id.
     * @param $agency Agency identifier.
     *
     * @return mixed  Doctrine entity.
     */
    abstract protected function update($id, $agency);

    /**
     * Delete an entity.
     *
     * @param $id     Entity id.
     * @param $agency Agency identifier.
     *
     * @return mixed  Doctrine entity.
     */
    abstract protected function delete($id, $agency);

    /**
     * RestBaseRequest constructor.
     *
     * @param MongoEM $em
     */
    public function __construct(MongoEM $em)
    {
        $this->em = $em;
    }

    /**
     * Facade for handling incoming requests.
     *
     * @param $method        HTTP request method.
     *
     * @return string        Request result message.
     * @throws RestException
     */
    public function handleRequest($method)
    {
        $this->validate();
        $requestResult = '';
        $requestBody = $this->getParsedBody();

        $id = $requestBody[$this->primaryIdentifier];
        $agency = $requestBody['agency'];

        switch ($method) {
            case 'POST':
                if (!$this->exists($id, $agency)) {
                    throw new RestException("Entity with id {$id}, agency {$agency} does not exist.");
                } else {
                    $updatedContent = $this->update($id, $agency);
                    $requestResult = 'Updated entity with id: '.$updatedContent->getId();
                }
                break;
            case 'PUT':
                if ($this->exists($id, $agency)) {
                    throw new RestException("Entity with id {$id}, agency {$agency} already exists.");
                } else {
                    $insertedContent = $this->insert();
                    $requestResult = 'Created entity with id: '.$insertedContent->getId();
                }
                break;
            case 'DELETE':
                if (!$this->exists($id, $agency)) {
                    throw new RestException("Entity with id {$id}, agency {$agency} does not exist.");
                } else {
                    $deletedContent = $this->delete($id, $agency);
                    $requestResult = 'Deleted entity with id: '.$deletedContent->getId();
                }
                break;
        }

        return $requestResult;
    }

    /**
     * Validate the incoming request against entity specific required values.
     *
     * @throws RestException
     */
    protected function validate()
    {
        $body = $this->getParsedBody();
        foreach ($this->requiredFields as $field) {
            if (empty($body[$field])) {
                throw new RestException('Required field "'.$field.'" has no value.');
            } elseif ($field == 'agency' && !$this->isChildAgencyValid($body[$field])) {
                throw new RestException("Tried to modify entity using agency {$body[$field]} which does not exist.");
            }
        }
    }

    /**
     * Validate child agency.
     *
     * @param $childAgency Agency identifier.
     *
     * @return bool
     */
    public function isChildAgencyValid($childAgency)
    {
        $agencyEntity = $this->getAgencyById($this->agencyId);

        if ($agencyEntity) {
            $children = $agencyEntity->getChildren();
            if (in_array($childAgency, $children) || $childAgency == $this->agencyId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepares the request payload.
     *
     * @param string $requestBody Requst payload as JSON.
     *
     * @throws RestException
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = json_decode($requestBody, true);
        $this->validateRequest();
    }

    /**
     * Validate request for basic sanity checks.
     *
     * @throws RestException
     */
    private function validateRequest()
    {
        $exceptionMessage = '';

        if (!$this->requestBody) {
            $exceptionMessage = 'Failed parsing request or empty payload.';
        } elseif (!$this->isRequestValid()) {
            $exceptionMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (empty($this->getParsedBody())) {
            $exceptionMessage = 'Empty request body.';
        }

        if (!empty($exceptionMessage)) {
            throw new RestException($exceptionMessage);
        }

        $this->agencyId = $this->getParsedCredentials()['agencyId'];
        $this->signature = $this->getParsedCredentials()['key'];
    }

    /**
     * Perform authorisation checks by checking agency and key.
     *
     * @return bool
     */
    private function isRequestValid()
    {
        $isValid = true;

        $requiredFields = [
            'agencyId',
            'key',
        ];

        $requestCredentials = $this->getParsedCredentials();
        foreach ($requiredFields as $field) {
            if (empty($requestCredentials[$field])) {
                $isValid = false;
            } elseif ($field == 'agencyId' && !$this->isAgencyValid($requestCredentials[$field])) {
                $isValid = false;
            } elseif ($field == 'key' && !$this->isSignatureValid($requestCredentials['agencyId'], $requestCredentials['key'])) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Check whether certain agency is known.
     *
     * @param $agencyId
     *
     * @return bool
     */
    public function isAgencyValid($agencyId)
    {
        $agency = $this->getAgencyById($agencyId);

        return !is_null($agency);
    }

    /**
     * Fetch an agency based on it's agency identifier.
     *
     * @param string $agencyId Agency identifier.
     *
     * @return Agency          Doctrine object.
     */
    public function getAgencyById($agencyId)
    {
        $agency = $this->em
            ->getRepository('AppBundle:Agency')
            ->findOneBy(['agencyId' => $agencyId]);

        return $agency;
    }

    /**
     * Check whether key signature is valid.
     *
     * @param string $agencyId  Agency identifier.
     * @param string $signature Signature to check.
     *
     * @return bool
     */
    public function isSignatureValid($agencyId, $signature)
    {
        $agency = $this->getAgencyById($agencyId);

        if ($agency) {
            $key = $agency->getKey();
            $targetSignature = sha1($agencyId.$key);

            if ($signature == $targetSignature) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the decoded request payload.
     *
     * @return mixed
     */
    public function getParsedBody()
    {
        return $this->requestBody['body'];
    }

    /**
     * Get the decoded request authorisation values.
     *
     * @return mixed
     */
    public function getParsedCredentials()
    {
        return $this->requestBody['credentials'];
    }
}
