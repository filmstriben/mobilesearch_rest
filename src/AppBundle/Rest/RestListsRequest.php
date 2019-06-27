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

        $this->primaryIdentifier = 'key';
        $this->requiredFields = [
            $this->primaryIdentifier,
            'agency',
            'nid',
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
            $this->primaryIdentifier => $id,
            'agency' => $agency,
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
        $updatedEntity = $this->prepare($loadedEntity);

        $dm = $this->em->getManager();
        $dm->flush();

        return $updatedEntity;
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
        $list->setKey($key);

        $nid = !empty($body['nid']) ? $body['nid'] : '0';
        $list->setAgency($nid);

        $agency = !empty($body['agency']) ? $body['agency'] : '000000';
        $list->setAgency($agency);

        $name = !empty($body['name']) ? $body['name'] : 'Undefined';
        $list->setName($name);

        $nids = !empty($body['nids']) ? $body['nids'] : [];
        $list->setNids($nids);

        $type = !empty($body['type']) ? $body['type'] : [];
        $list->setType($type);

        $promoted = !empty($body['promoted']) ? $body['promoted'] : [];
        $list->setPromoted($promoted);

        $weight = !empty($body['weight']) ? $body['weight'] : 0;
        $list->setWeight($weight);

        return $list;
    }

    /**
     * Fetches list content.
     *
     * @param string $agency    Agency identifier.
     * @param int $amount       Number of entries to fetch.
     * @param int $skip         Number of entries to skip.
     * @param int $promoted     Filter items by promoted value.
     * @param bool $countOnly   Fetch only number of entries.
     *
     * @return Lists[]
     */
    public function fetchLists($agency, $amount = 10, $skip = 0, $promoted = 1, $countOnly = FALSE)
    {
        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Lists::class);

        $qb->field('agency')->equals($agency);

        if ($countOnly) {
            $qb->count();
        }
        else {
            $qb->skip($skip)->limit($amount);
        }

        if (-1 !== (int)$promoted) {
            $qb->field('promoted')->equals((boolean)$promoted);
        }

        return $qb->getQuery()->execute();
    }
}
