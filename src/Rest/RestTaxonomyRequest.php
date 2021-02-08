<?php

namespace App\Rest;

use App\Exception\RestException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

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

    public function __construct(MongoEM $em)
    {
        parent::__construct($em);
    }

    protected function get($id, $agency)
    {
    }

    protected function exists($id, $agency)
    {
    }

    protected function insert()
    {
    }

    protected function update($id, $agency)
    {
    }

    protected function delete($id, $agency)
    {
    }

    public function fetchVocabularies($agency, $contentType)
    {
        $content = $this->em
            ->getRepository('AppBundle:Content')
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
//                     foreach ($vocabulary['terms'] as $term) {
//                         $vocabularies[$vocabularyName]['terms'][] = $term;
//                     }

//                     $vocabularies[$vocabularyName]['terms'] = array_values(array_unique($vocabularies[$vocabularyName]['terms']));
                }
            }
        }

        return $vocabularies;
    }

    public function fetchTermSuggestions($agency, $vocabulary, $contentType, $query)
    {
        $field = 'taxonomy.'.$vocabulary.'.terms';
        $pattern = '/'.$query.'/i';

        $result = $this->em->getRepository('AppBundle:Content')->findBy(
            [
                'agency' => $agency,
                'type' => $contentType,
                $field => ['$in' => [new \MongoRegex($pattern)]],
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

    public function fetchRelatedContent($agency, array $vocabulary, array $terms)
    {
        if (count($vocabulary) != count($terms)) {
            throw new RestException('Number of vocabulary and terms count mismatch.');
        }

        $criteria = [
            'agency' => $agency,
        ];

        foreach ($vocabulary as $k => $item) {
            $field = 'taxonomy.'.$item.'.terms';
            $criteria[$field] = ['$in' => explode(',', $terms[$k])];
        }

        $content = $this->em->getRepository('AppBundle:Content')->findBy($criteria);

        return $content;
    }
}
