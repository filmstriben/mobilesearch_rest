<?php

namespace AppBundle\Controller;

use AppBundle\Document\Content;
use AppBundle\Document\Lists;
use AppBundle\Document\Menu;
use AppBundle\Exception\RestException;
use AppBundle\Rest\RestBaseRequest;
use AppBundle\Rest\RestContentRequest;
use AppBundle\Rest\RestListsRequest;
use AppBundle\Rest\RestMenuRequest;
use AppBundle\Rest\RestTaxonomyRequest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RestController extends Controller
{
    private $lastStatus = false;
    private $lastMessage = '';
    private $lastMethod;
    private $lastItems = [];
    private $rawContent;

    /**
     * @ApiDoc(
     *     description="Persists an entry of content.",
     *     section="Content",
     *     requirements={
     *         {
     *             "name"="credentials",
     *             "dataType"="string",
     *             "description"="Request credentials.",
     *             "format"="json"
     *         },
     *         {
     *             "name"="body",
     *             "dataType"="string",
     *             "description"="Request body.",
     *             "format"="json"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\Output"
     *     }
     * )
     * @Route("/content")
     * @Method({"PUT"})
     */
    public function contentCreateAction(Request $request)
    {
        return $this->contentDispatcher($request);
    }

    /**
     * @ApiDoc(
     *     description="Updates an existing entry of content.",
     *     section="Content",
     *     requirements={
     *         {
     *             "name"="credentials",
     *             "dataType"="json",
     *             "description"="Request credentials.",
     *             "format"="json"
     *         },
     *         {
     *             "name"="body",
     *             "dataType"="json",
     *             "description"="Request body.",
     *             "format"="json"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\Output"
     *     }
     * )
     * @Route("/content")
     * @Method({"POST"})
     */
    public function contentUpdateAction(Request $request)
    {
        return $this->contentDispatcher($request);
    }

    /**
     * @ApiDoc(
     *     description="Deletes an entry of content.",
     *     section="Content",
     *     requirements={
     *         {
     *             "name"="credentials",
     *             "dataType"="json",
     *             "description"="Request credentials."
     *         },
     *         {
     *             "name"="body",
     *             "dataType"="json",
     *             "description"="Request body."
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\Output"
     *     }
     * )
     * @Route("/content")
     * @Method({"DELETE"})
     */
    public function contentDeleteAction(Request $request)
    {
        return $this->contentDispatcher($request);
    }

    /**
     * Dispatches content related requests.
     *
     * @param Request $request Incoming Request object.
     *
     * @return Response        Outgoing Response object.
     */
    public function contentDispatcher(Request $request)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $em = $this->get('doctrine_mongodb');
        $restContentRequest = new RestContentRequest($em);

        return $this->relay($restContentRequest);
    }

    /**
     * 'node' parameter, when specified, ignores any other parameters.<br />
     * 'status' parameter legend: '-1' - all content, '0' - not published, '1' - published.<br />
     * 'order' parameter legend: 'ASC' - ascending, 'DESC' - descending.
     *
     * @ApiDoc(
     *     description="Fetches content entries.",
     *     section="Content",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="key",
     *             "dataType"="string",
     *             "description"="Authentication key."
     *         }
     *     },
     *     parameters={
     *         {
     *             "name"="node",
     *             "dataType"="integer",
     *             "description"="Fetch content by id (nid). Comma separated list of id's are supported.",
     *             "required"=false
     *         },
     *         {
     *             "name"="amount",
     *             "dataType"="integer",
     *             "description"="Specifies how many results to fetch. Defaults to 10.",
     *             "required"=false
     *         },
     *         {
     *             "name"="skip",
     *             "dataType"="integer",
     *             "description"="Specifies how many results to skip. Defaults to 0.",
     *             "required"=false
     *         },
     *         {
     *             "name"="sort",
     *             "dataType"="string",
     *             "description"="Specifies by which field the result is sorted. Defaults to 'fields.title.value'.",
     *             "required"=false
     *         },
     *         {
     *             "name"="order",
     *             "dataType"="string",
     *             "description"="Specifies the sort order. Defaults to ascending.",
     *             "required"=false,
     *             "format"="ASC|DESC"
     *         },
     *         {
     *             "name"="type",
     *             "dataType"="string",
     *             "description"="Filters the entities by the value stored in 'type' field.",
     *             "required"=false
     *         },
     *         {
     *             "name"="status",
     *             "dataType"="integer",
     *             "description"="Filters the entities by the value stored in 'fields.status.value' field. Defaults to -1.",
     *             "required"=false,
     *             "format"="-1|0|1"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\ContentOutput"
     *     }
     * )
     *
     *
     *
     * @Route("/content/fetch")
     * @Method({"GET"})
     */
    public function contentFetchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
            'node' => null,
            'amount' => 10,
            'skip' => 0,
            'sort' => 'fields.title.value',
            'order' => 'ASC',
            'type' => null,
            'status' => RestContentRequest::STATUS_ALL,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        $em = $this->get('doctrine_mongodb');
        $restContentRequest = new RestContentRequest($em);

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            unset($fields['key']);
            $items = call_user_func_array([$restContentRequest, 'fetchFiltered'], $fields);

            if (!empty($items)) {
                /** @var Content $item */
                foreach ($items as $item) {
                    $this->lastItems[] = [
                        'id' => $item->getId(),
                        'nid' => $item->getNid(),
                        'agency' => $item->getAgency(),
                        'type' => $item->getType(),
                        'fields' => $item->getFields(),
                        'taxonomy' => $item->getTaxonomy(),
                        'list' => $item->getList(),
                    ];
                }

                $this->lastStatus = true;
            }
        }

        return $this->setResponse($this->lastStatus, $this->lastMessage, $this->lastItems);
    }

    /**
     * 'query' parameter can accept regular expressions, case insensitive, when searching within any content fields, unless
     * the search is made within the 'taxonomy', where a direct match is performed.<br />
     * 'query' and 'field' parameters must always match in count.<br />
     * There might multiple pairs of 'query' and 'field' parameters. Multiple pairs of search conditions are
     * treated as a logical AND.<br />
     * To use multiple conditions, add square brackets after the parameter in the query string.<br />
     * 'query' parameter can receive multiple values, separated by comma. This would result for content that is
     * searched, to contain at least one term from the comma separated list.
     * E.g.: <pre>query[]=editorial&field[]=type&query[]=Hjemmefra,At%20home&field[]=taxonomy.field_realm.terms</pre>
     * This would match content with having 'editorial' value as 'type' and 'taxonomy.field_realm.terms' either
     * containing 'Hjemmefra', or 'At home' term.
     *
     * @ApiDoc(
     *     description="Searches content entries by certain criteria(s).",
     *     section="Content",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="key",
     *             "dataType"="string",
     *             "description"="Authentication key."
     *         }
     *     },
     *     parameters={
     *         {
     *             "name"="query",
     *             "dataType"="string",
     *             "description"="Search query.",
     *             "required"=false
     *         },
     *         {
     *             "name"="field",
     *             "dataType"="string",
     *             "description"="Content entity field to search in.",
     *             "required"=false
     *         },
     *         {
     *             "name"="amount",
     *             "dataType"="integer",
     *             "description"="Specifies how many results to fetch. Defaults to 10.",
     *             "required"=false
     *         },
     *         {
     *             "name"="skip",
     *             "dataType"="integer",
     *             "description"="Specifies how many results to skip. Defaults to 0.",
     *             "required"=false
     *         },
     *     },
     *     output={
     *         "class": "AppBundle\IO\ContentOutput"
     *     }
     * )
     * @Route("/content/search")
     * @Method({"GET"})
     */
    public function contentSearchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
            'query' => null,
            'field' => null,
            'amount' => 10,
            'skip' => 0,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];

            if (in_array($field, ['query', 'field'])) {
                $fields[$field] = array_filter((array)$fields[$field]);
            }
        }

        $em = $this->get('doctrine_mongodb');
        $restContentRequest = new RestContentRequest($em);

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (!empty($fields['query']) && !empty($fields['field'])) {
            unset($fields['key']);

            try {
                $suggestions = call_user_func_array([$restContentRequest, 'fetchSuggestions'], $fields);

                foreach ($suggestions as $suggestion) {
                    $fields = $suggestion->getFields();
                    $this->lastItems[] = [
                        'id' => $suggestion->getId(),
                        'nid' => $suggestion->getNid(),
                        'title' => isset($fields['title']['value']) ? $fields['title']['value'] : '',
                        'changed' => isset($fields['changed']['value']) ? $fields['changed']['value'] : '',
                    ];
                }

                $this->lastStatus = true;
            } catch (RestException $e) {
                $this->lastMessage = $e->getMessage();
            }
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems
        );
    }

    /**
     * @ApiDoc(
     *     description="Persists a menu entry.",
     *     section="Menu",
     *     requirements={
     *         {
     *             "name"="credentials",
     *             "dataType"="string",
     *             "description"="Request credentials.",
     *             "format"="json"
     *         },
     *         {
     *             "name"="body",
     *             "dataType"="string",
     *             "description"="Request body.",
     *             "format"="json"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\Output"
     *     }
     * )
     * @Route("/menu")
     * @Method({"PUT"})
     */
    public function menuCreateAction(Request $request)
    {
        return $this->menuDispatcher($request);
    }

    /**
     * @ApiDoc(
     *     description="Updates a menu entry.",
     *     section="Menu",
     *     requirements={
     *         {
     *             "name"="credentials",
     *             "dataType"="string",
     *             "description"="Request credentials.",
     *             "format"="json"
     *         },
     *         {
     *             "name"="body",
     *             "dataType"="string",
     *             "description"="Request body.",
     *             "format"="json"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\Output"
     *     }
     * )
     * @Route("/menu")
     * @Method({"POST"})
     */
    public function menuUpdateAction(Request $request)
    {
        return $this->menuDispatcher($request);
    }

    /**
     * @ApiDoc(
     *     description="Deletes a menu entry.",
     *     section="Menu",
     *     requirements={
     *         {
     *             "name"="credentials",
     *             "dataType"="string",
     *             "description"="Request credentials.",
     *             "format"="json"
     *         },
     *         {
     *             "name"="body",
     *             "dataType"="string",
     *             "description"="Request body.",
     *             "format"="json"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\Output"
     *     }
     * )
     * @Route("/menu")
     * @Method({"DELETE"})
     */
    public function menuDeleteAction(Request $request)
    {
        return $this->menuDispatcher($request);
    }

    /**
     * Dispatcher menu related requests.
     *
     * @param Request $request Incoming Request object.
     *
     * @return Response        Outgoing Response object.
     */
    public function menuDispatcher(Request $request)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $em = $this->get('doctrine_mongodb');
        $rmr = new RestMenuRequest($em);

        return $this->relay($rmr);
    }

    /**
     * @ApiDoc(
     *     description="Fetches menu entries.",
     *     section="Menu",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="key",
     *             "dataType"="string",
     *             "description"="Authentication key."
     *         }
     *     },
     *     parameters={
     *         {
     *             "name"="amount",
     *             "dataType"="integer",
     *             "description"="Specifies how many results to fetch. Defaults to 10.",
     *             "required"=false
     *         },
     *         {
     *             "name"="skip",
     *             "dataType"="integer",
     *             "description"="Specifies how many results to skip. Defaults to 0.",
     *             "required"=false
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\MenuOutput"
     *     }
     * )
     * @Route("/menu/fetch")
     * @Method({"GET"})
     */
    public function menuFetchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
            'amount' => 10,
            'skip' => 0,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        $em = $this->get('doctrine_mongodb');
        $restMenuRequest = new RestMenuRequest($em);

        if (!$restMenuRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            unset($fields['key']);

            /** @var Menu[] $suggestions */
            $menuEntities = call_user_func_array([$restMenuRequest, 'fetchMenus'], $fields);

            /** @var Menu $menuEntity */
            foreach ($menuEntities as $menuEntity) {
                $this->lastItems[] = [
                    'mlid' => $menuEntity->getMlid(),
                    'agency' => $menuEntity->getAgency(),
                    'type' => $menuEntity->getType(),
                    'name' => $menuEntity->getName(),
                    'url' => $menuEntity->getUrl(),
                    'weight' => $menuEntity->getOrder(),
                    'enabled' => $menuEntity->getEnabled(),
                ];
            }

            $this->lastStatus = true;
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems
        );
    }

    /**
     * @ApiDoc(
     *     description="Persists a list entry.",
     *     section="List",
     *     requirements={
     *         {
     *             "name"="credentials",
     *             "dataType"="string",
     *             "description"="Request credentials.",
     *             "format"="json"
     *         },
     *         {
     *             "name"="body",
     *             "dataType"="string",
     *             "description"="Request body.",
     *             "format"="json"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\Output"
     *     }
     * )
     * @Route("/list")
     * @Method({"PUT"})
     */
    public function listCreateAction(Request $request)
    {
        return $this->listDispatcher($request);
    }

    /**
     * @ApiDoc(
     *     description="Updates a list entry.",
     *     section="List",
     *     requirements={
     *         {
     *             "name"="credentials",
     *             "dataType"="string",
     *             "description"="Request credentials.",
     *             "format"="json"
     *         },
     *         {
     *             "name"="body",
     *             "dataType"="string",
     *             "description"="Request body.",
     *             "format"="json"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\Output"
     *     }
     * )
     * @Route("/list")
     * @Method({"POST"})
     */
    public function listUpdateAction(Request $request)
    {
        return $this->listDispatcher($request);
    }

    /**
     * @ApiDoc(
     *     description="Deletes a list entry.",
     *     section="List",
     *     requirements={
     *         {
     *             "name"="credentials",
     *             "dataType"="string",
     *             "description"="Request credentials.",
     *             "format"="json"
     *         },
     *         {
     *             "name"="body",
     *             "dataType"="string",
     *             "description"="Request body.",
     *             "format"="json"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\Output"
     *     }
     * )
     * @Route("/list")
     * @Method({"DELETE"})
     */
    public function listDeleteAction(Request $request)
    {
        return $this->listDispatcher($request);
    }

    /**
     * Dispatcher list related requests.
     *
     * @param Request $request Incoming Request object.
     *
     * @return Response        Outgoing Response object.
     */
    public function listDispatcher(Request $request)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $em = $this->get('doctrine_mongodb');
        $rlr = new RestListsRequest($em);

        return $this->relay($rlr);
    }

    /**
     * @ApiDoc(
     *     description="Fetches list entries.",
     *     section="List",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="key",
     *             "dataType"="string",
     *             "description"="Authentication key."
     *         }
     *     },
     *     parameters={
     *         {
     *             "name"="amount",
     *             "dataType"="integer",
     *             "description"="Specifies how many results to fetch. Defaults to 10.",
     *             "required"=false
     *         },
     *         {
     *             "name"="skip",
     *             "dataType"="integer",
     *             "description"="Specifies how many results to skip. Defaults to 0.",
     *             "required"=false
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\ListOutput"
     *     }
     * )
     * @Route("/list/fetch")
     * @Method({"GET"})
     */
    public function listFetchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
            'amount' => 10,
            'skip' => 0,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        $em = $this->get('doctrine_mongodb');
        $restListsRequest = new RestListsRequest($em);

        if (!$restListsRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            unset($fields['key']);

            /** @var Lists[] $suggestions */
            $suggestions = call_user_func_array([$restListsRequest, 'fetchLists'], $fields);

            foreach ($suggestions as $suggestion) {
                $this->lastItems[] = [
                    'agency' => $suggestion->getAgency(),
                    'key' => $suggestion->getKey(),
                    'name' => $suggestion->getName(),
                    'nids' => $suggestion->getNids(),
                    'type' => $suggestion->getType(),
                    'promoted' => $suggestion->getPromoted(),
                    'weight' => $suggestion->getWeight(),
                ];
            }

            $this->lastStatus = true;
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems
        );
    }

    /**
     * Replaced by '/taxonomy/vocabularies' route.
     *
     * @ApiDoc(
     *     description="Fetches vocabularies for a certain content entity type.",
     *     section="Taxonomy",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="key",
     *             "dataType"="string",
     *             "description"="Authentication key."
     *         },
     *         {
     *             "name"="contentType",
     *             "dataType"="string",
     *             "description"="Content type.",
     *             "required"="true"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\TaxonomyOutput"
     *     },
     *     deprecated="true",
     * )
     * @Route("/taxonomy/vocabularies/{contentType}")
     * @Method({"GET"})
     */
    public function taxonomyAction(Request $request, $contentType)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rtr = new RestTaxonomyRequest($em);

        if (!$rtr->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            $vocabularies = $rtr->fetchVocabularies($fields['agency'], $contentType);

            $this->lastItems = $vocabularies;
            $this->lastStatus = true;
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems
        );
    }

    /**
     * 'contentType' - content entity type identifier, value in 'type' field of the content entity.<br />
     *
     * @ApiDoc(
     *     description="Fetches vocabularies for a certain content entity type.",
     *     section="Taxonomy",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="key",
     *             "dataType"="string",
     *             "description"="Authentication key."
     *         }
     *     },
     *     parameters={
     *         {
     *             "name"="contentType",
     *             "dataType"="string",
     *             "description"="Content type.",
     *             "required"="true"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\TaxonomyOutput"
     *     }
     * )
     * @Route("/taxonomy/vocabularies")
     * @Method({"GET"})
     */
    public function taxonomyNewAction(Request $request)
    {
        $response = $this->forward(
            'AppBundle:Rest:taxonomy',
            [
                'request' => $request,
                'contentType' => $request->query->get('contentType'),
            ]
        );

        return $response;
    }

    /**
     * Replaced by '/taxonomy/terms' route.
     *
     * @ApiDoc(
     *     description="Fetches term suggestions matching a search string.",
     *     section="Taxonomy",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="key",
     *             "dataType"="string",
     *             "description"="Authentication key."
     *         },
     *         {
     *             "name"="vocabulary",
     *             "dataType"="string",
     *             "description"="Vocabulary name.",
     *             "required"="true"
     *         },
     *         {
     *             "name"="contentType",
     *             "dataType"="string",
     *             "description"="Content type.",
     *             "required"="true"
     *         },
     *         {
     *             "name"="query",
     *             "dataType"="string",
     *             "description"="Search query.",
     *             "required"="true"
     *         },
     *     },
     *     deprecated="true",
     *     output={
     *         "class": "AppBundle\IO\TaxonomyTermOutput"
     *     }
     * )
     * @Route("/taxonomy/terms/{vocabulary}/{contentType}/{query}")
     * @Method({"GET"})
     */
    public function taxonomySearchAction(Request $request, $vocabulary, $contentType, $query)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rtr = new RestTaxonomyRequest($em);

        if (!$rtr->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            $suggestions = $rtr->fetchTermSuggestions($fields['agency'], $vocabulary, $contentType, $query);

            $this->lastItems = $suggestions;
            $this->lastStatus = true;
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems
        );
    }

    /**
     * 'vocabulary' - name of the vocabulary under the 'taxonomy' key in content entity.
     * To fetch all available vocabularies for a certain content entity type, see '/taxonomy/vocabularies' route.<br />
     * 'contentType' - content entity type identifier, value in 'type' field of the content entity.<br />
     * 'query' - search string, accepts regular expressions, case insensitive.
     *
     * @ApiDoc(
     *     description="Fetches term suggestions matching a search string.",
     *     section="Taxonomy",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="key",
     *             "dataType"="string",
     *             "description"="Authentication key."
     *         }
     *     },
     *     parameters={
     *         {
     *             "name"="vocabulary",
     *             "dataType"="string",
     *             "description"="Vocabulary name.",
     *             "required"="true"
     *         },
     *         {
     *             "name"="contentType",
     *             "dataType"="string",
     *             "description"="Content type.",
     *             "required"="true"
     *         },
     *         {
     *             "name"="query",
     *             "dataType"="string",
     *             "description"="Search query.",
     *             "required"="true"
     *         }
     *     },
     *     output={
     *         "class": "AppBundle\IO\TaxonomyTermOutput"
     *     }
     * )
     * @Route("/taxonomy/terms")
     * @Method({"GET"})
     */
    public function taxonomySearchNewAction(Request $request)
    {
        $response = $this->forward(
            'AppBundle:Rest:taxonomySearch',
            [
                'request' => $request,
                'vocabulary' => $request->query->get('vocabulary'),
                'contentType' => $request->query->get('contentType'),
                'query' => $request->query->get('query'),
            ]
        );

        return $response;
    }

    /**
     * Replaced by '/content/search' route.
     *
     * @ApiDoc(
     *     description="Fetches content entries containing certain vocabulary terms.",
     *     section="Content",
     *     requirements={},
     *     output={
     *         "class": "AppBundle\IO\ContentOutput"
     *     },
     *     deprecated=true
     * )
     * @Route("/content/related")
     * @Method({"GET"})
     */
    public function taxonomyRelatedContentAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
            'vocabulary' => null,
            'terms' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rtr = new RestTaxonomyRequest($em);

        if (!$rtr->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            $this->lastItems = [];

            try {
                $items = $rtr->fetchRelatedContent(
                    $fields['agency'],
                    (array)$fields['vocabulary'],
                    (array)$fields['terms']
                );

                if (!empty($items)) {
                    foreach ($items as $item) {
                        $this->lastItems[] = [
                            'id' => $item->getId(),
                            'nid' => $item->getNid(),
                            'agency' => $item->getAgency(),
                            'type' => $item->getType(),
                            'fields' => $item->getFields(),
                            'taxonomy' => $item->getTaxonomy(),
                            'list' => $item->getList(),
                        ];
                    }
                }

                $this->lastStatus = true;
            } catch (RestException $e) {
                $this->lastMessage = $e->getMessage();
            }
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems
        );
    }

    /**
     * Processes incoming requests, except for the ones sent with GET method.
     *
     * @param RestBaseRequest $genericRequest Generic request object wrapper.
     *
     * @return Response                       Outgoing Response object.
     */
    private function relay(RestBaseRequest $genericRequest)
    {
        try {
            $genericRequest->setRequestBody($this->rawContent);
            $result = $genericRequest->handleRequest($this->lastMethod);
            $this->lastMessage = $result;
            $this->lastStatus = true;
        } catch (RestException $exc) {
            $this->lastMessage = 'Request fault: '.$exc->getMessage();
        } catch (\Exception $exc) {
            $this->lastMessage = 'Generic fault: '.$exc->getMessage();
        }

        $response = $this->setResponse($this->lastStatus, $this->lastMessage);

        return $response;
    }

    /**
     * Prepares an http response.
     *
     * @param bool $status    Request processed status.
     * @param string $message Debug message, if any.
     * @param array $items    Response items, if any.
     *
     * @return Response       Outgoing Response object.
     */
    private function setResponse($status = true, $message = '', $items = [])
    {
        $responseContent = [
            'status' => $status,
            'message' => $message,
            'items' => $items,
        ];

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
