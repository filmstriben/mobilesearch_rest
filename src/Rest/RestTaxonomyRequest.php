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
        $content = $this->em
            ->getRepository(Content::class)
            ->findBy(
                [
                    'agency' => $agency,
                    'type' => $contentType,
                ]
            );

        $vocabularies = [];
        foreach ($content as $node) {
            foreach ($node->getTaxonomy() as $vocabularyName => $vocabulary) {
                if (!empty($vocabulary['terms']) && is_array($vocabulary['terms'])) {
                    $vocabularies[$vocabularyName] = $vocabulary['name'];
                }
            }
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
        $field = 'taxonomy.'.$vocabulary.'.terms';

        $result = $this->em->getRepository(Content::class)->findBy(
            [
                'agency' => $agency,
                'type' => $contentType,
                $field => ['$in' => [new MongoRegex($query, 'i')]],
            ]
        );

        $terms = [];
        foreach ($result as $content) {
            $taxonomy = $content->getTaxonomy();
            if (isset($taxonomy[$vocabulary]) && is_array($taxonomy[$vocabulary]['terms'])) {
                foreach ($taxonomy[$vocabulary]['terms'] as $term) {
                    $pattern = '/'.$query.'/i';
                    if (preg_match($pattern, $term)) {
                        $terms[] = $term;
                    }
                }
            }
        }

        $terms = array_values(array_unique($terms));

        return $terms;
    }
}
