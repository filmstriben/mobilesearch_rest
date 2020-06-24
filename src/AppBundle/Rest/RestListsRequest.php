<?php

namespace AppBundle\Rest;

use AppBundle\Document\Lists;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

/**
 * Class RestListsRequest
 *
 * Handle list specific requests.
 */
class RestListsRequest extends RestBaseRequest
{
    /**
     * RestListsRequest constructor.
     *
     * @param MongoEM $em
     */
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);

        $this->primaryIdentifier = 'lid';
        $this->requiredFields = [
            $this->primaryIdentifier,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function exists($id, $agency)
    {
        $entity = $this->get($id, $agency);

        return !is_null($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function get($id, $agency)
    {
        $criteria = [
            $this->primaryIdentifier => (int) $id,
        ];

        $entity = $this->em
            ->getRepository('AppBundle:Lists')
            ->findOneBy($criteria);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function insert()
    {
        $entity = $this->prepare(new Lists());

        $dm = $this->em->getManager();
        $dm->persist($entity);
        $dm->flush();

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function update($id, $agency)
    {
        $loadedEntity = $this->get($id, $agency);

        // Small hack that actually replaces the entity, instead of an update.
        // Mongo lower than 3.6 fails to update and entity if it contains keys
        // starting with $ sign.
        $dm = $this->em->getManager();
        $dm->remove($loadedEntity);
        $dm->flush();

        return $this->insert();
    }

    /**
     * {@inheritdoc}
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
     * Prepares the list entry structure.
     *
     * @param Lists $list
     *
     * @return Lists
     */
    public function prepare(Lists $list)
    {
        $body = $this->getParsedBody();

        $key = !empty($body[$this->primaryIdentifier]) ? $body[$this->primaryIdentifier] : 0;
        $list->setLid((int) $key);

        $name = !empty($body['name']) ? $body['name'] : 'Undefined';
        $list->setName($name);

        $type = !empty($body['type']) ? $body['type'] : [];
        $list->setType($type);

        $promoted = !empty($body['promoted']) ? $body['promoted'] : [];
        $list->setPromoted($promoted);

        $weight = !empty($body['weight']) ? $body['weight'] : 0;
        $list->setWeight($weight);

        $criteria = !empty($body['criteria']) ? $body['criteria'] : [];
        $list->setCriteria($criteria);

        return $list;
    }

    /**
     * Fetches list content.
     *
     * @param int $amount       Number of entries to fetch.
     * @param int $skip         Number of entries to skip.
     * @param int $promoted     Filter items by promoted value.
     * @param bool $countOnly   Fetch only number of entries.
     *
     * @return Lists[]
     */
    public function fetchLists($amount = 10, $skip = 0, $promoted = 1, $countOnly = false)
    {
        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Lists::class);

        if ($countOnly) {
            $qb->count();
        } else {
            $qb->skip($skip)->limit($amount);
        }

        if (-1 !== (int)$promoted) {
            $qb->field('promoted')->equals((boolean)$promoted);
        }

        return $qb->getQuery()->execute();
    }
}
