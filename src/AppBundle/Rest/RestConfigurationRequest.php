<?php

namespace AppBundle\Rest;

use AppBundle\Document\Agency;
use AppBundle\Document\Configuration;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

/**
 * Class RestConfigurationRequest.
 */
class RestConfigurationRequest extends RestBaseRequest
{
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);

        $this->primaryIdentifier = 'cid';
        $this->requiredFields = [
            $this->primaryIdentifier,
            'agency',
        ];
    }

    protected function exists($id, $agency)
    {
        $entity = $this->get($id, $agency);

        return !is_null($entity);
    }

    protected function get($id, $agency)
    {
        $criteria = [
            $this->primaryIdentifier => $id,
            'agency' => $agency,
        ];

        return $this->em
            ->getRepository(Configuration::class)
            ->findOneBy($criteria);
    }

    protected function insert()
    {
        $entity = $this->prepare(new Configuration());

        $dm = $this->em->getManager();
        $dm->persist($entity);
        $dm->flush();

        return $entity;
    }

    protected function update($id, $agency)
    {
        $loadedEntity = $this->get($id, $agency);
        $updatedEntity = $this->prepare($loadedEntity);

        $dm = $this->em->getManager();
        $dm->flush();

        return $updatedEntity;
    }

    protected function delete($id, $agency)
    {
        $entity = $this->get($id, $agency);

        $dm = $this->em->getManager();
        $dm->remove($entity);
        $dm->flush();

        return $entity;
    }

    /**
     * Prepares the menu entity structure.
     *
     * @param Configuration $configuration
     *
     * @return Configuration
     */
    public function prepare(Configuration $configuration)
    {
        $body = $this->getParsedBody();

        $cid = !empty($body['cid']) ? $body['cid'] : sha1(mt_rand());
        $configuration->setCid($cid);

        $agency = !empty($body['agency']) ? $body['agency'] : '000000';
        $configuration->setAgency($agency);

        $settings = !empty($body['settings']) ? $body['settings']: [];
        var_dump($settings);
        if (is_array($settings)) {
            $configuration->mergeSettings($settings);
        }

        return $configuration;
    }

    /**
     * Fetches configuration for a given agency.
     *
     * @param string $agency
     *
     * @return Configuration[]
     */
    public function getConfiguration($agency)
    {
        $agencyEntity = $this->em
            ->getRepository(Agency::class)
            ->findOneBy(['agencyId' => $agency]);
        $childAgencies = $agencyEntity->getChildren();

        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Configuration::class);

        $childAgencies[] = $agency;
        $qb->field('agency')->in($childAgencies);

        return $qb->getQuery()->execute();
    }
}
