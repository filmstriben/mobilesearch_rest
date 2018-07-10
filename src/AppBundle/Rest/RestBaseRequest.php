<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use AppBundle\Exception\RestException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

abstract class RestBaseRequest
{
    protected $agencyId = null;
    protected $signature = null;
    protected $requestBody = null;
    protected $requiredFields = array();
    protected $em = null;
    protected $primaryIdentifier = '';

    abstract protected function get($id, $agency);

    abstract protected function exists($id, $agency);

    abstract protected function insert();

    abstract protected function update($id, $agency);

    abstract protected function delete($id, $agency);

    public function __construct(MongoEM $em)
    {
        $this->em = $em;
    }

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
                    $requestResult = 'Updated entity with id: ' . $updatedContent->getId();
                }
                break;
            case 'PUT':
                if ($this->exists($id, $agency)) {
                    throw new RestException("Entity with id {$id}, agency {$agency} already exists.");
                } else {
                    $insertedContent = $this->insert();
                    $requestResult = 'Created entity with id: ' . $insertedContent->getId();
                }
                break;
            case 'DELETE':
                if (!$this->exists($id, $agency)) {
                    throw new RestException("Entity with id {$id}, agency {$agency} does not exist.");
                } else {
                    $deletedContent = $this->delete($id, $agency);
                    $requestResult = 'Deleted entity with id: ' . $deletedContent->getId();
                }
                break;
        }

        return $requestResult;
    }

    protected function validate()
    {
        $body = $this->getParsedBody();
        foreach ($this->requiredFields as $field) {
            if (empty($body[$field])) {
                throw new RestException('Required field "' . $field . '" has no value.');
            } elseif ($field == 'agency' && !$this->isChildAgencyValid($body[$field])) {
                throw new RestException("Tried to modify entity using agency {$body[$field]} which does not exist.");
            }
        }
    }

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

    public function setRequestBody($requestBody)
    {
        $this->requestBody = json_decode($requestBody, true);
        $this->validateRequest();
    }

    private function validateRequest()
    {
        $exceptionMessage = '';

        if (!$this->requestBody) {
            $exceptionMessage = 'Failed parsing request.';
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

    private function isRequestValid()
    {
        $isValid = true;

        $requiredFields = array(
            'agencyId',
            'key',
        );

        $requestCredentials = $this->getParsedCredentials();
        foreach ($requiredFields as $field) {
            if (empty($requestCredentials[$field])) {
                $isValid = false;
            } elseif ($field == 'agencyId' && !$this->isAgencyValid($requestCredentials[$field])) {
                $isValid = false;
            } elseif ($field == 'key' && !$this->isSignatureValid($requestCredentials['agencyId'],
                    $requestCredentials['key'])) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    public function isAgencyValid($agencyId)
    {
        $agency = $this->getAgencyById($agencyId);

        return !is_null($agency);
    }

    public function getAgencyById($agencyId)
    {
        $agency = $this->em
            ->getRepository('AppBundle:Agency')
            ->findOneBy(array('agencyId' => $agencyId));

        return $agency;
    }

    public function isSignatureValid($agencyId, $signature)
    {
        $agency = $this->getAgencyById($agencyId);

        if ($agency) {
            $key = $agency->getKey();
            $targetSignature = sha1($agencyId . $key);

            if ($signature == $targetSignature) {
                return true;
            }
        }

        return false;
    }

    public function getParsedBody()
    {
        return $this->requestBody['body'];
    }

    public function getParsedCredentials()
    {
        return $this->requestBody['credentials'];
    }
}
