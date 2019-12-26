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
    /**
     * RestConfigurationRequest constructor.
     *
     * @param MongoEM $em
     *   Entity manager.
     */
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);

        $this->primaryIdentifier = 'agency';
        $this->requiredFields = [
            $this->primaryIdentifier
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function exists($id, $agency)
    {
        $entity = $this->get($id, $agency);

        return !is_null($entity);
    }

    /**
     * {@inheritDoc}
     */
    protected function get($id, $agency)
    {
        // Ignore id, since agency is considered the unique id here.
        $criteria = [
            $this->primaryIdentifier => $agency,
        ];

        return $this->em
            ->getRepository(Configuration::class)
            ->findOneBy($criteria);
    }

    /**
     * {@inheritDoc}
     */
    protected function insert()
    {
        $entity = $this->prepare(new Configuration());

        $dm = $this->em->getManager();
        $dm->persist($entity);
        $dm->flush();

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    protected function update($id, $agency)
    {
        $loadedEntity = $this->get($id, $agency);
        $updatedEntity = $this->prepare($loadedEntity);

        $dm = $this->em->getManager();
        $dm->flush();

        return $updatedEntity;
    }

    /**
     * {@inheritDoc}
     */
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

        $agency = !empty($body['agency']) ? $body['agency'] : '000000';
        $configuration->setAgency($agency);

        $settings = !empty($body['settings']) ? $body['settings']: [];
        if (is_array($settings)) {
            // TODO: The settings array must be validated prior write.
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
