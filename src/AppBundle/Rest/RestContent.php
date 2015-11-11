<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

use AppBundle\Rest\RestBaseRequest;
use AppBundle\Document\Content as FSContent;
use AppBundle\Exception\RestException;

class RestContent extends RestBaseRequest
{
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);

        $this->primaryIdentifier = 'nid';
        $this->requiredFields = array(
            $this->primaryIdentifier,
            'agency',
        );
    }

    protected function validate()
    {
        $body = $this->getParsedBody();
        foreach ($this->requiredFields as $field)
        {
            if (empty($body[$field]))
            {
                throw new RestException('Required field "' . $field . '" has no value.');
            }
            elseif ($field == 'agency' && !$this->isChildAgencyValid($body[$field]))
            {
                throw new RestException("Tried to modify content using agency {$body[$field]} which does not exist.");
            }
        }
    }

    public function isChildAgencyValid($childAgency)
    {
        $agencyEntity = $this->getAgencyById($this->agencyId);

        if ($agencyEntity)
        {
            $children = $agencyEntity->getChildren();
            if (in_array($childAgency, $children) || $childAgency == $this->agencyId)
            {
                return TRUE;
            }
        }

        return FALSE;
    }

    public function exists($id, $agency)
    {
        $entity = $this->get($id, $agency);

        return !is_null($entity);
    }

    public function get($id, $agency)
    {
        $criteria = array(
            $this->primaryIdentifier => $id,
            'agency' => $agency,
        );

        $content = $this->em
            ->getRepository('AppBundle:Content')
            ->findOneBy($criteria);

        return $content;
    }

    public function handleRequest($method)
    {
        $this->validate();
        $requestResult = '';
        $requestBody = $this->getParsedBody();

        $nid = $requestBody[$this->primaryIdentifier];
        $agency = $requestBody['agency'];

        switch ($method)
        {
            case 'POST':
                if (!$this->exists($nid, $agency))
                {
                    throw new RestException("Content with id {$nid}, agency {$agency} does not exist.");
                }
                else {
                    $updatedContent = $this->update($nid, $agency);
                    $requestResult = 'Updated content with id: ' . $updatedContent->getId();
                }
                break;
            case 'PUT':
                if ($this->exists($nid, $agency))
                {
                    throw new RestException("Content with id {$nid}, agency {$agency} already exists.");
                }
                else {
                    $insertedContent = $this->insert();
                    $requestResult = 'Created content with id: ' . $insertedContent->getId();
                }
                break;
            case 'DELETE':
                if (!$this->exists($nid, $agency))
                {
                    throw new RestException("Content with id {$nid}, agency {$agency} does not exist.");
                }
                else {
                    $deletedContent = $this->delete($nid, $agency);
                    $requestResult = 'Deleted content with id: ' . $deletedContent->getId();
                }
                break;
        }

        return $requestResult;
    }

    private function insert()
    {
        $contentObject = $this->prepare(new FSContent());

        $dm = $this->em->getManager();
        $dm->persist($contentObject);
        $dm->flush();

        return $contentObject;
    }

    private function update($id, $agency)
    {
        $contentEntity = $this->get($id, $agency);
        $updatedEntity = $this->prepare($contentEntity);

        $dm = $this->em->getManager();
        $dm->flush();

        return $updatedEntity;
    }

    private function delete($id, $agency)
    {
        $contentObject = $this->get($id, $agency);

        $dm = $this->em->getManager();
        $dm->remove($contentObject);
        $dm->flush();

        return $contentObject;
    }

    public function prepare(FSContent $content)
    {
        $body = $this->getParsedBody();

        $nid = !empty($body[$this->primaryIdentifier]) ? $body[$this->primaryIdentifier] : 0;
        $content->setNid($nid);

        $agency = !empty($body['agency']) ? $body['agency'] : '000000';
        $content->setAgency($agency);

        $type = !empty($body['type']) ? $body['type'] : 'undefined';
        $content->setType($type);

        $fields = !empty($body['fields']) ? $body['fields'] : array();
        $content->setFields($fields);

        $taxonomy = !empty($body['taxonomy']) ? $body['taxonomy'] : array();
        $content->setTaxonomy($taxonomy);

        $list = !empty($body['list']) ? $body['list'] : array();
        $content->setList($list);

        return $content;
    }
}
