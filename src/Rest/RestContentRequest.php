<?php

namespace App\Rest;

use App\Document\Content;
use App\Exception\RestException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Comparison;
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
     * @param \Doctrine\Persistence\ManagerRegistry $em Entity manager.
     */
    public function __construct(ManagerRegistry $em)
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
            ->getRepository('App:Content')
            ->findOneBy($criteria);

        return $content;
    }

    /**
     * Fetches content that fulfills certain criteria.
     *
     * @param string $id
     *   Fetch these specific entries (_id field match, multiple values separated by comma).
     * @param string $node
     *   Fetch these specific entries (nid field match, multiple values separated by comma).
     * @param int $amount
     *   Fetch this amount of entries.
     * @param int $skip
     *   Skip this amount of entries.
     * @param string $sort
     *   Sort field.
     * @param string $dir
     *   Sort direction. Either ASC or DESC.
     * @param string $type
     *   Entry type (type field).
     * @param string $status
     *   Entry status (fields.status.value field).
     * @param bool $countOnly
     *   Get only the number of results.
     *
     * @return \App\Document\Content[]
     *   A set of entities.
     */
    public function fetchFiltered(
        $id = null,
        $node = null,
        $amount = 10,
        $skip = 0,
        $sort = '',
        $dir = '',
        $type = null,
        $status = self::STATUS_PUBLISHED,
        $countOnly = FALSE
    ) {
        if (!empty($id)) {
            $ids = explode(',', $id);
            return $this->fetchContent($ids, '_id', $countOnly);
        } elseif (!empty($node)) {
            $nids = explode(',', $node);
            return $this->fetchContent($nids, 'nid', $countOnly);
        }

        /** @var \Doctrine\ODM\MongoDB\Query\Builder $qb */
        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Content::class);

        if ($countOnly) {
            $qb->count();
        }
        else {
            $qb->skip($skip)->limit($amount);
        }

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

        return $qb->getQuery()->execute();
    }

    /**
     * Searches content suggestions based on certain criteria.
     *
     * @param array $query
     *   Search query.
     * @param array $field
     *   Field to search in.
     * @param int $amount
     *   Fetch this amount of suggestions.
     * @param int $skip
     *   Skip this amount of suggestions.
     * @param bool $countOnly
     *   Return only count of results.
     *
     * @return \App\Document\Content[]
     *   A set of suggested entities.
     *
     * @throws RestException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @deprecated
     */
    public function fetchSuggestions(array $query, array $field, $amount = 10, $skip = 0, $countOnly = FALSE)
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

        if ($countOnly) {
            $qb->count();
        }
        else {
            $qb->skip($skip)->limit($amount);
        }

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
     * @param array $ids      Content id's.
     * @param string $field   Field where to seek the id's.
     * @param bool $countOnly Fetch only number of entries.
     *
     * @return Content[]|int      A set of entities or their count.
     */
    public function fetchContent(array $ids, $field = 'nid', $countOnly = FALSE)
    {
        if (empty($ids)) {
            return $countOnly ? 0 : [];
        }

        // Mongo has strict type check, and since 'nid' is stored as int
        // convert the value to int as well.
        array_walk(
            $ids,
            function (&$v) use ($field) {
                switch ($field) {
                    case 'nid':
                        $v = (int)$v;
                        break;
                    case 'id':
                        $v = new \MongoId($v);
                        break;
                }
            }
        );

        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Content::class);

        if ($countOnly) {
            $qb->count();
        }

        $qb->field($field)->in($ids);

        return $qb->getQuery()->execute();
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
                    } else {
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
