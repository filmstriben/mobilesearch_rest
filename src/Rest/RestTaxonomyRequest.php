<?php

namespace App\Rest;

use App\Document\Content;
use App\Exception\RestException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;
use MongoDB\BSON\Regex as MongoRegex;

/**
 * @deprecated
 * Taxonomy is a part of Content entity fields.
 * This class does not represent any entity, since there's no taxonomy entity. No CRUD logic
 * is intended for it.
 * All methods from here should be moved to Content entity repository, since whole logic
 * is coupled with that entity.
 *
 * Class RestTaxonomyRequest
 */
class RestTaxonomyRequest extends RestBaseRequest
{

    /**
     * RestTaxonomyRequest constructor.
     *
     * @param \Doctrine\Bundle\MongoDBBundle\ManagerRegistry $em
     */
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);
    }

    /**
     * {@inheritDoc}
     */
    protected function get($id, $agency)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function exists($id, $agency)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function insert()
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function update($id, $agency)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function delete($id, $agency)
    {
    }

    /**
     * Fetches a list of vocabularies.
     *
     * @param string $agency
     *   Agency identifier.
     * @param string $contentType
     *   Node type.
     *
     * @return array
     */
    public function fetchVocabularies($agency, $contentType)
    {
        $collection = $this->em->getManager()->getDocumentCollection(Content::class);

        $pipeline = [
            ['$match' => ['agency' => $agency, 'type' => $contentType]],
            ['$project' => ['taxonomy' => ['$objectToArray' => '$taxonomy']]],
            ['$unwind' => '$taxonomy'],
            ['$match' => [
                'taxonomy.v.terms' => ['$exists' => true],
                'taxonomy.v.terms.0' => ['$exists' => true],
            ]],
            ['$group' => ['_id' => '$taxonomy.k', 'name' => ['$first' => '$taxonomy.v.name']]],
            ['$sort' => ['_id' => 1]],
        ];

        $vocabularies = [];
        foreach ($collection->aggregate($pipeline) as $doc) {
            $vocabularies[$doc['_id']] = $doc['name'];
        }

        return $vocabularies;
    }

    /**
     * Fetches term suggestions for a certain vocabulary of a certain node type.
     *
     * @param string $agency
     *   Agency identifier.
     * @param string $vocabulary
     *   Vocabulary name.
     * @param string $contentType
     *   Node type.
     * @param string $query
     *   Search query.
     *
     * @return array
     */
    public function fetchTermSuggestions($agency, $vocabulary, $contentType, $query)
    {
        $field = 'taxonomy.' . $vocabulary . '.terms';
        $collection = $this->em->getManager()->getDocumentCollection(Content::class);

        $pipeline = [
            // Narrow the document set using the existing agency+type index.
            ['$match' => [
                'agency' => $agency,
                'type'   => $contentType,
                $field   => ['$elemMatch' => ['$regex' => $query, '$options' => 'i']],
            ]],
            // Explode the terms array so each term becomes its own document.
            ['$unwind' => '$' . $field],
            // Keep only terms that match the query regex.
            ['$match' => [$field => new MongoRegex($query, 'i')]],
            // Deduplicate.
            ['$group' => ['_id' => '$' . $field]],
            ['$sort'  => ['_id' => 1]],
        ];

        $terms = [];
        foreach ($collection->aggregate($pipeline) as $doc) {
            $terms[] = (string) $doc['_id'];
        }

        return $terms;
    }
}
