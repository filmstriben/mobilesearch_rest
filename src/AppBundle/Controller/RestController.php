<?php

namespace AppBundle\Controller;

use AppBundle\Document\Configuration;
use AppBundle\Document\Content;
use AppBundle\Document\Lists;
use AppBundle\Document\Menu;
use AppBundle\Exception\RestException;
use AppBundle\Repositories\ListsRepository;
use AppBundle\Rest\RestBaseRequest;
use AppBundle\Rest\RestConfigurationRequest;
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
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "agencyId": "999999",
     *     "key": "933b0de149ae93e8b954b9c1513582e1f4f1d0d9"
     *   },
     *   "body": {
     *     "nid": 4962,
     *     "agency": "999999",
     *     "type": "os",
     *     "list": [],
     *     "fields": {
     *       "title": {
     *         "name": "Title",
     *         "value": "Min smukke nabo",
     *         "attr": []
     *       }
     *     },
     *     "taxonomy": {
     *       "field_category": {
     *         "name": "Category",
     *         "terms": []
     *       },
     *       "field_realm": {
     *         "name": "Realm",
     *         "terms": [
     *           "At library"
     *         ]
     *       }
     *     }
     *   }
     * }
     * </pre>
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
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "agencyId": "999999",
     *     "key": "933b0de149ae93e8b954b9c1513582e1f4f1d0d9"
     *   },
     *   "body": {
     *     "nid": 4962,
     *     "agency": "999999",
     *     "type": "os",
     *     "list": [],
     *     "fields": {
     *       "title": {
     *         "name": "Title",
     *         "value": "Min smukke nabo",
     *         "attr": []
     *       }
     *     },
     *     "taxonomy": {
     *       "field_category": {
     *         "name": "Category",
     *         "terms": []
     *       },
     *       "field_realm": {
     *         "name": "Realm",
     *         "terms": [
     *           "At library"
     *         ]
     *       }
     *     }
     *   }
     * }
     * </pre>
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
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "agencyId": "999999",
     *     "key": "933b0de149ae93e8b954b9c1513582e1f4f1d0d9"
     *   },
     *   "body": {
     *     "nid": 4962,
     *     "agency": "999999"
     *   }
     * }
     * </pre>
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
     * <p>'id' parameter, when specified, ignores any other parameters.</p>
     * <p>'node' parameter, like above, ignores any other parameters. The difference between 'id' and 'nid', is that 'id'
     * is a unique identifier for every piece content stored. Alternatively, 'nid' can repeat, since it might be the
     * same content pushed by different agencies. Please note, that this might produce duplicates
     * due to the fact that content might exist for several agencies. To fetch an exact entry(ies) use 'id'.</p>
     * <p>'status' parameter legend: '-1' - all content, '0' - not published, '1' - published.</p>
     * <p>'order' parameter legend: 'ASC' - ascending, 'DESC' - descending.</p>
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
     *             "name"="id",
     *             "dataType"="string",
     *             "description"="Fetch content by internal id. Comma separated list of id's are supported.",
     *             "required"=false
     *         },
     *         {
     *             "name"="node",
     *             "dataType"="string",
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
     * @Route("/content/fetch")
     * @Method({"GET"})
     */
    public function contentFetchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
            'id' => null,
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

        $hits = 0;

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            unset($fields['agency'], $fields['key']);
            try {
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

                $fields['countOnly'] = true;
                $hits = call_user_func_array([$restContentRequest, 'fetchFiltered'], $fields);
            } catch (RestException $e) {
                // TODO: Log this instead.
                $this->lastMessage = $e->getMessage();
            }
        }

        return $this->setResponse($this->lastStatus, $this->lastMessage, $this->lastItems, $hits);
    }

    /**
     * <p>'query' parameter can accept regular expressions, case insensitive, when searching within any content fields, unless
     * the search is made within the 'taxonomy', where a direct match is performed.</p>
     * <p>'query' and 'field' parameters must always match in count.<br />
     * There might multiple pairs of 'query' and 'field' parameters. Multiple pairs of search conditions are
     * treated as a logical AND.</p>
     * <p>To use multiple conditions, add square brackets after the parameter in the query string.<br />
     * 'query' parameter can receive multiple values, separated by comma. This would result for content that is
     * searched, to contain at least one term from the comma separated list.
     * E.g.: <pre>query[]=editorial&field[]=type&query[]=Hjemmefra,At%20home&field[]=taxonomy.field_realm.terms</pre>
     * This query string would fetch content with having 'editorial' value as 'type' and 'taxonomy.field_realm.terms'
     * containing either 'Hjemmefra', or 'At home' terms.</p>
     * <p>Add a 'format=short' pair to the query string to get a plain list of title suggestions.
     * E.g.: <pre>query[]=editorial&field[]=type&query[]=Hjemmefra,At%20home&field[]=taxonomy.field_realm.terms&format=short</pre></p>
     *
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
     *         {
     *             "name"="format",
     *             "dataType"="string",
     *             "description"="Use 'short' value to get a plain list of suggested titles.",
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
            'format' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];

            if (in_array($field, ['query', 'field'])) {
                $fields[$field] = array_filter((array)$fields[$field]);
            }
        }

        $em = $this->get('doctrine_mongodb');
        $restContentRequest = new RestContentRequest($em);

        $hits = 0;

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (!empty($fields['query']) && !empty($fields['field'])) {
            unset($fields['agency'], $fields['key']);

            try {
                $format = $fields['format'];
                unset($fields['format']);
                $suggestions = call_user_func_array([$restContentRequest, 'fetchSuggestions'], $fields);

                /** @var Content $suggestion */
                foreach ($suggestions as $suggestion) {
                    $suggestionFields = $suggestion->getFields();

                    if ('short' == $format) {
                        $this->lastItems[] = isset($suggestionFields['title']['value']) ? $suggestionFields['title']['value'] : '';
                    } else {
                        $this->lastItems[] = [
                            'id' => $suggestion->getId(),
                            'nid' => $suggestion->getNid(),
                            'agency' => $suggestion->getAgency(),
                            'title' => isset($suggestionFields['title']['value']) ? $suggestionFields['title']['value'] : '',
                            'changed' => isset($suggestionFields['changed']['value']) ? $suggestionFields['changed']['value'] : '',
                        ];
                    }
                }


                $fields['countOnly'] = true;
                $hits = call_user_func_array([$restContentRequest, 'fetchSuggestions'], $fields);

                $this->lastStatus = true;
            } catch (RestException $e) {
                // TODO: Log this instead.
                $this->lastMessage = $e->getMessage();
            }
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems,
            $hits
        );
    }

    /**
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "agencyId": "999999",
     *     "key": "933b0de149ae93e8b954b9c1513582e1f4f1d0d9"
     *   },
     *   "body": {
     *     "mlid": 461,
     *     "agency": "999999",
     *     "type": "left_menu",
     *     "name": "Home",
     *     "url": "\/",
     *     "order": -18,
     *     "enabled": true
     *    }
     * }
     * </pre>
     *
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
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "agencyId": "999999",
     *     "key": "933b0de149ae93e8b954b9c1513582e1f4f1d0d9"
     *   },
     *   "body": {
     *     "mlid": 461,
     *     "agency": "999999",
     *     "type": "left_menu",
     *     "name": "Home",
     *     "url": "\/",
     *     "order": -18,
     *     "enabled": true
     *    }
     * }
     * </pre>
     *
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
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "agencyId": "999999",
     *     "key": "933b0de149ae93e8b954b9c1513582e1f4f1d0d9"
     *   },
     *   "body": {
     *     "mlid": 461,
     *     "agency": "999999"
     *    }
     * }
     * </pre>
     *
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

        $hits = 0;

        if (!$restMenuRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            unset($fields['key']);

            try {
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

                $fields['countOnly'] = true;
                $hits = call_user_func_array([$restMenuRequest, 'fetchMenus'], $fields);
            } catch (RestException $e) {
                // TODO: Log this instead.
                $this->lastMessage = $e->getMessage();
            }
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems,
            $hits
        );
    }

    /**
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "agencyId": "999999",
     *     "key": "933b0de149ae93e8b954b9c1513582e1f4f1d0d9"
     *   },
     *   "body": {
     *     "nid": 999999,
     *     "key": "784ad029a05c3e710b9283b2882e417b",
     *     "agency": "999999",
     *     "type": "dynamic",
     *     "name": "Some list",
     *     "promoted": 1,
     *     "weight": null,
     *     "nids": []
     *   }
     * }
     * </pre>
     *
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
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "agencyId": "999999",
     *     "key": "933b0de149ae93e8b954b9c1513582e1f4f1d0d9"
     *   },
     *   "body": {
     *     "nid": 999999,
     *     "key": "784ad029a05c3e710b9283b2882e417b",
     *     "agency": "999999",
     *     "type": "dynamic",
     *     "name": "Some list",
     *     "promoted": 1,
     *     "weight": null,
     *     "nids": []
     *   }
     * }
     * </pre>
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
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "agencyId": "999999",
     *     "key": "933b0de149ae93e8b954b9c1513582e1f4f1d0d9"
     *   },
     *   "body": {
     *     "nid": 999999,
     *     "key": "784ad029a05c3e710b9283b2882e417b",
     *     "agency": "999999",
     *   }
     * }
     * </pre>
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
     * <p>'promoted' parameter possible values:</p>
     * <p>'-1' - all lists, '0' - not promoted, '1' - promoted.</p>
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
     *         },
     *         {
     *             "name"="promoted",
     *             "dataType"="integer",
     *             "description"="Filter items by promoted value. Defaults to 1 - promoted only.",
     *             "required"=false
     *         },
     *         {
     *              "name"="itemType",
     *              "dataType"="string",
     *              "description"="Lists should contain items only if this content type.",
     *              "required"=false
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
            'promoted' => 1,
            'itemType' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        $em = $this->get('doctrine_mongodb');
        $restListsRequest = new RestListsRequest($em);

        $hits = 0;

        if (!$restListsRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            unset($fields['key']);

            try {
                $itemType = $fields['itemType'];
                unset($fields['itemType']);

                /** @var Lists[] $suggestions */
                $suggestions = call_user_func_array([$restListsRequest, 'fetchLists'], $fields);
                /** @var ListsRepository $listsRepository */
                $listsRepository = $em->getRepository(Lists::class);

                foreach ($suggestions as $suggestion) {
                    // In case filtering node types is needed.
                    if (count($suggestion->getNids()) > 0 && $itemType) {
                        $suggestion = $listsRepository->filterAttachedItems($suggestion, $itemType);
                    }

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

                $fields['countOnly'] = true;
                $hits = call_user_func_array([$restListsRequest, 'fetchLists'], $fields);
            } catch (RestException $e) {
                // TODO: Log this instead.
                $this->lastMessage = $e->getMessage();
            }
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems,
            $hits
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
     * <p>'contentType' - content entity type identifier, value in 'type' field of the content entity.</p>
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
     * <p>'vocabulary' - name of the vocabulary under the 'taxonomy' key in content entity.
     * To fetch all available vocabularies for a certain content entity type, see '/taxonomy/vocabularies' route.</p>
     * <p>'contentType' - content entity type identifier, value in 'type' field of the content entity.</p>
     * <p>'query' - search string, accepts regular expressions, case insensitive.</p>
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
     * Payload example:
     * <pre>
     * {
     *   "credentials": {
     *     "key": "3339b2bbfbb515cc1aa873861c7a738845c7dc49",
     *     "agencyId": "100000"
     *   },
     *   "body": {
     *     "cid": "c90e277c62a6508cb9e5cc7efbf976326b4d009d",
     *     "agency": "100001",
     *     "settings": {
     *       "some_setting": "setting_value"
     *     }
     *   }
     * }
     * </pre>
     * @ApiDoc(
     *     section="Configuration"
     * )
     * @Route("/configuration")
     * @Method({"PUT"})
     */
    public function configurationCreateAction(Request $request)
    {
        return $this->configurationDispatcher($request);
    }

    /**
     * @ApiDoc(
     *     section="Configuration"
     * )
     * @Route("/configuration")
     * @Method({"POST"})
     */
    public function configurationUpdateAction(Request $request)
    {
        return $this->configurationDispatcher($request);
    }

    /**
     * @ApiDoc(
     *     section="Configuration"
     * )
     * @Route("/configuration")
     * @Method({"DELETE"})
     */
    public function configurationDeleteAction(Request $request)
    {
        return $this->configurationDispatcher($request);
    }

    /**
     * @ApiDoc(
     *     description="Fetches content entries.",
     *     section="Configuration",
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
     *     output={
     *         "class": "AppBundle\IO\ConfigurationOutput"
     *     }
     * )
     * @Route("/configuration")
     * @Method({"GET"})
     */
    public function configurationFetchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        $em = $this->get('doctrine_mongodb');
        $restConfigurationRequest = new RestConfigurationRequest($em);

        if (!$restConfigurationRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            unset($fields['key']);
            try {
                /** @var Configuration[] $items */
                $items = call_user_func_array([$restConfigurationRequest, 'getConfiguration'], $fields);
                $settings = [];
                foreach ($items as $k => $item) {
                    $settings[$item->getAgency()] = [
                        'cid' => $item->getCid(),
                        'settings' => $item->getSettings()
                    ];
                }

                $this->lastItems = $settings;
                $this->lastStatus = true;
            } catch (RestException $e) {
                // TODO: Log this instead.
                $this->lastMessage = $e->getMessage();
            }
        }

        return $this->setResponse($this->lastStatus, $this->lastMessage, $this->lastItems);
    }

    /**
     * Dispatches configuration related requests.
     *
     * @param Request $request Incoming Request object.
     *
     * @return Response        Outgoing Response object.
     */
    public function configurationDispatcher(Request $request)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $em = $this->get('doctrine_mongodb');
        $restContentRequest = new RestConfigurationRequest($em);

        return $this->relay($restContentRequest);
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

        return $this->setResponse($this->lastStatus, $this->lastMessage);
    }

    /**
     * Prepares an http response.
     *
     * @param bool $status    Request processed status.
     * @param string $message Debug message, if any.
     * @param array $items    Response items, if any.
     * @param int $hits       Number of available items.
     *
     * @return Response       Outgoing Response object.
     */
    private function setResponse($status = true, $message = '', $items = [], $hits = null)
    {
        $responseContent = [
            'status' => $status,
            'message' => $message,
            'items' => $items,
        ];

        if (null !== $hits) {
            $responseContent['hits'] = (int) $hits;
        }

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
