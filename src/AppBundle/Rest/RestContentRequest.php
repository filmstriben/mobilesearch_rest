<?php

namespace AppBundle\Rest;

use AppBundle\Document\Content;
use AppBundle\Exception\RestException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;
use Symfony\Component\Filesystem\Filesystem as FSys;

/**
 * Class RestContentRequest
 *
 * Handle content specific requests.
 */
class RestContentRequest extends RestBaseRequest
{
    const STATUS_ALL = '-1';

    const STATUS_PUBLISHED = '1';

    const STATUS_UNPUBLISHED = '0';

    const IMAGE_UPLOADS_PATH = '../web/storage/images/';

    /**
     * RestContentRequest constructor.
     *
     * @param MongoEM $em Entity manager.
     */
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);

        $this->primaryIdentifier = 'nid';
        $this->requiredFields = [
            $this->primaryIdentifier,
            'agency',
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
            $this->primaryIdentifier => (int)$id,
            'agency' => $agency,
        ];

        $content = $this->em
            ->getRepository('AppBundle:Content')
            ->findOneBy($criteria);

        return $content;
    }

    /**
     * Fetches content that fulfills certain criteria.
     *
     * @param string $agency
     * @param int $node
     * @param int $amount
     * @param int $skip
     * @param string $sort
     * @param string $dir
     * @param string $type
     * @param string $status
     *
     * @return Content[]
     */
    public function fetchFiltered(
        $agency,
        $node = null,
        $amount = 10,
        $skip = 0,
        $sort = '',
        $dir = '',
        $type = null,
        $status = self::STATUS_PUBLISHED
    ) {
        if (!empty($node)) {
            return $this->fetchContent(explode(',', $node), $agency);
        }

        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Content::class);

        $qb->field('agency')->equals($agency);

        if ($type) {
            $qb->field('type')->equals($type);
        }

        if ($sort && $dir) {
            $qb->sort($sort, $dir);
        }

        $possibleStatuses = [
            self::STATUS_ALL,
            self::STATUS_PUBLISHED,
            self::STATUS_UNPUBLISHED,
        ];
        // Set a status filter only if it differs from the default one.
        if (self::STATUS_ALL != $status && in_array($status, $possibleStatuses)) {
            $qb->field('fields.status.value')->equals($status);
        }

        $qb->skip($skip)->limit($amount);

        return $qb->getQuery()->execute();
    }

    /**
     * Searches content suggestions based on certain criteria.
     *
     * @param string $agency
     * @param array $query
     * @param array $field
     * @param int $amount
     * @param int $skip
     *
     * @return mixed
     *
     * @throws RestException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function fetchSuggestions($agency, array $query, array $field, $amount = 10, $skip = 0)
    {
        if (count($query) != count($field)) {
            throw new RestException('Query and fields parameters count mismatch.');
        }

        reset($query);
        reset($field);

        /** @var \Doctrine\ODM\MongoDB\Query\Builder $qb */
        $qb = $this
            ->em
            ->getManager()
            ->createQueryBuilder(Content::class);

        $qb->field('agency')->equals($agency);

        while ($currentQuery = current($query)) {
            $currentField = current($field);

            if (preg_match('/taxonomy\..*\.terms/', $currentField)) {
                $qb
                    ->field($currentField)
                    ->in(explode(',', $currentQuery));
            } else {
                $qb
                    ->field($currentField)
                    ->equals(new \MongoRegex('/'.$currentQuery.'/i'));
            }

            next($query);
            next($field);
        }

        $qb->skip($skip)->limit($amount);

        return $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function insert()
    {
        $entity = $this->prepare(new Content());

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
     * Fetches content by id.
     *
     * @param array $ids     Content id's.
     * @param string $agency Agency number.
     *
     * @return Content[]
     */
    public function fetchContent(array $ids, $agency)
    {
        if (empty($ids)) {
            return [];
        }

        // Mongo has strict type check, and since 'nid' is stored as int
        // convert the value to int as well.
        array_walk(
            $ids,
            function (&$v) {
                $v = (int)$v;
            }
        );

        $criteria = [
            'agency' => $agency,
            'nid' => ['$in' => $ids],
        ];

        $entities = $this->em
            ->getRepository('AppBundle:Content')
            ->findBy($criteria);

        return $entities;
    }

    /**
     * Prepares the content entry structure.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function prepare(Content $content)
    {
        $body = $this->getParsedBody();

        $nid = !empty($body[$this->primaryIdentifier]) ? $body[$this->primaryIdentifier] : 0;
        $content->setNid($nid);

        $agency = !empty($body['agency']) ? $body['agency'] : '000000';
        $content->setAgency($agency);

        $type = !empty($body['type']) ? $body['type'] : 'undefined';
        $content->setType($type);

        $fields = !empty($body['fields']) ? $body['fields'] : [];
        $fields = $this->parseImageFields($fields);
        $content->setFields($fields);

        $taxonomy = !empty($body['taxonomy']) ? $body['taxonomy'] : [];
        $content->setTaxonomy($taxonomy);

        $list = !empty($body['list']) ? $body['list'] : [];
        $content->setList($list);

        return $content;
    }

    /**
     * Takes base64 image data from content fields and creates a physical file.
     *
     * TODO: This method does a lot, which can be split or a service implemented instead.
     *
     * @param array $fields Content entity fields.
     *
     * @return array        Same fields set, yet with images as paths to physical files.
     */
    private function parseImageFields(array $fields)
    {
        $imageFields = [
            'field_images',
            'field_background_image',
            'field_ding_event_title_image',
            'field_ding_event_list_image',
            'field_ding_library_title_image',
            'field_ding_library_list_image',
            'field_ding_news_title_image',
            'field_ding_news_list_image',
            'field_ding_page_title_image',
            'field_ding_page_list_image',
        ];

        $fieldsToProcess = array_intersect_key(array_flip($imageFields), $fields);

        foreach (array_flip($fieldsToProcess) as $fieldName) {
            // Note that values are passed by reference for convenience.
            $fieldToProcess = &$fields[$fieldName];

            if (!is_array($fieldToProcess['value'])) {
                $fieldToProcess['value'] = [$fieldToProcess['value']];
            }

            foreach ($fieldToProcess['value'] as $k => $imageBase64Contents) {
                if (empty($imageBase64Contents) || empty($fieldToProcess['attr'][$k]) || !preg_match('/^image\/(jpg|jpeg|gif|png)$/', $fieldToProcess['attr'][$k])) {
                    continue;
                }

                // Do not store base64 contents in any case.
                $fieldToProcess['value'][$k] = null;

                $imageFileExtension = explode('/', $fieldToProcess['attr'][$k]);
                if (empty($imageFileExtension[1])) {
                    continue;
                }

                $extension = $imageFileExtension[1];

                $fileSystem = new FSys();

                if (!is_writable(self::IMAGE_UPLOADS_PATH)) {
                    // TODO: Maybe log something in that case.
                    continue;
                }

                $finalImageDirectory = self::IMAGE_UPLOADS_PATH.$this->agencyId;
                if (!$fileSystem->exists($finalImageDirectory)) {
                    $fileSystem->mkdir($finalImageDirectory);
                }

                $fileName = sha1($imageBase64Contents.$this->agencyId).'.'.$extension;
                $finalImagePath = $finalImageDirectory.'/'.$fileName;
                $fileSystem->dumpFile($finalImagePath, base64_decode($imageBase64Contents));

                if ($fileSystem->exists($finalImagePath)) {
                    // Simple check whether resulting file is an image.
                    // If not, remove the upload immediately.
                    if (function_exists('getimagesize') && getimagesize($finalImagePath)) {
                        $fieldToProcess['value'][$k] = 'files/'.$this->agencyId.'/'.$fileName;
                    }
                    else {
                        $fileSystem->remove($finalImagePath);
                    }
                }
            }

            // Reset indexes.
            $fieldToProcess['value'] = array_values($fieldToProcess['value']);
            $fieldToProcess['attr'] = array_values($fieldToProcess['attr']);
        }

        return $fields;
    }
}
