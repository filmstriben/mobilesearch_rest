<?php

namespace App\Controller;

use App\Ast\Walker\MongoTreeWalker;
use App\Document\Configuration;
use App\Document\Content;
use App\Document\Lists;
use App\Document\Menu;
use App\Exception\RestException;
use App\Rest\RestBaseRequest;
use App\Rest\RestConfigurationRequest;
use App\Rest\RestContentRequest;
use App\Rest\RestListsRequest;
use App\Rest\RestMenuRequest;
use App\Rest\RestTaxonomyRequest;
use App\Services\SearchQueryParser;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;

/**
 * Class RestController.
 */
final class RestController extends AbstractController
{
    private $lastStatus = false;
    private $lastMessage = '';
    private $lastMethod;
    private $lastItems = [];
    private $rawContent;

    /**
     * Insert new content entry.
     *
     * @Route("/content", methods={"PUT"})
     * @OA\Put(
     *     description="",
     *     tags={"Content"}
     * )
     */
    public function contentCreateAction(Request $request)
    {
        return $this->contentDispatcher($request);
    }

    /**
     * Update a content entry.
     *
     * @Route("/content", methods={"POST"})
     * @OA\Post(
     *     description="",
     *     tags={"Content"}
     * )
     */
    public function contentUpdateAction(Request $request)
    {
        return $this->contentDispatcher($request);
    }

    /**
     * Deletes a content entry.
     *
     * @Route("/content", methods={"DELETE"})
     * @OA\Delete(
     *     description="",
     *     tags={"Content"}
     * )
     */
    public function contentDeleteAction(Request $request)
    {
        return $this->contentDispatcher($request);
    }

    /**
     * Dispatches content related requests.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contentDispatcher(Request $request, ManagerRegistry $dm, LoggerInterface $logger)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $restContentRequest = new RestContentRequest($dm);

        return $this->relay($restContentRequest, $logger);
    }

    /**
     * Fetches a specific set of content.
     *
     * @Route("/content/fetch", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Content"}
     * )
     */
    public function contentFetchAction(Request $request, ManagerRegistry $dm)
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

        $restContentRequest = new RestContentRequest($dm);

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
                        $this->lastItems[] = $item->toArray();
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
     * Searches content by matching values for specific fields.
     *
     * @Route("/content/search", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Content"},
     *     deprecated=true
     * )
     */
    public function contentSearchAction(Request $request, ManagerRegistry $dm)
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

        $restContentRequest = new RestContentRequest($dm);

        $hits = 0;

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (!empty($fields['query']) && !empty($fields['field'])) {
            unset($fields['agency'], $fields['key']);

            try {
                $format = $fields['format'];
                unset($fields['format']);
                $suggestions = call_user_func_array([$restContentRequest, 'fetchSuggestions'], $fields);

                /** @var \App\Document\Content $suggestion */
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
     * Searches content by specific phrase and ranks the results.
     *
     * @Route("/content/search-ranked", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Content"}
     * )
     *
     * TODO: Test coverage.
     */
    public function contentSearchRankedAction(Request $request, ManagerRegistry $dm)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
            'q' => null,
            'amount' => 10,
            'skip' => 0,
            'format' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        // Hard upper limit to 100 items per request.
        $fields['amount'] = $fields['amount'] > 100 ? 100 : $fields['amount'];

        $restContentRequest = new RestContentRequest($dm);

        $hits = 0;

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (!empty($fields['q'])) {
            /** @var \App\Repositories\ContentRepository $contentRepository */
            $contentRepository = $dm->getRepository(Content::class);
            $suggestions = $contentRepository->fetchSuggestions(
                $fields['q'],
                $fields['amount'],
                $fields['skip']
            );

            /** @var \App\Document\Content $suggestion */
            foreach ($suggestions as $suggestion) {
                $suggestionFields = $suggestion->getFields();

                switch ($fields['format']) {
                    case 'short':
                        $this->lastItems[] = isset($suggestionFields['title']['value']) ? $suggestionFields['title']['value'] : '';
                        break;
                    case 'full':
                        $this->lastItems[] = $suggestion->toArray(true);
                        break;
                    default:
                        $this->lastItems[] = [
                            'id' => $suggestion->getId(),
                            'nid' => $suggestion->getNid(),
                            'agency' => $suggestion->getAgency(),
                            'title' => isset($suggestionFields['title']['value']) ? $suggestionFields['title']['value'] : '',
                            'changed' => isset($suggestionFields['changed']['value']) ? $suggestionFields['changed']['value'] : '',
                            'score' => $suggestion->getScore()
                        ];
                }
            }

            $fields['countOnly'] = true;
            $hits = $contentRepository->fetchSuggestions(
                $fields['q'],
                $fields['amount'],
                $fields['skip'],
                true
            );

            $this->lastStatus = true;
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems,
            $hits
        );
    }

    /**
     * Searches content using compound and/or rules.
     *
     * @Route("/content/search-extended", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Content"}
     * )
     * TODO: Test coverage.
     */
    public function searchExtendedAction(Request $request, ManagerRegistry $dm, SearchQueryParser $queryParser, LoggerInterface $logger)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
            'q' => null,
            'amount' => 10,
            'skip' => 0,
            'format' => null,
            'sort' => null,
            'order' => 'asc',
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        if (!in_array($fields['order'], ['asc', 'desc'])) {
            $fields['order'] = 'asc';
        }

        $restContentRequest = new RestContentRequest($dm);

        $hits = 0;

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (!empty($fields['q'])) {
            unset($fields['agency'], $fields['key']);

            /** @var \Doctrine\ODM\MongoDB\Query\Builder $qb */
            $qb = $dm
                ->getManager()
                ->createQueryBuilder(Content::class);

            $query = $fields['q'];

            try {
                $ast = $queryParser->parse($query);
                $treeWalker = new MongoTreeWalker($qb);
                $ast->transform($treeWalker);
            } catch (\RuntimeException $exception) {
                $logger->error($exception->getMessage());

                $this->lastMessage = $exception->getMessage();

                return $this->setResponse(
                    $this->lastStatus,
                    $this->lastMessage,
                    $this->lastItems,
                    $hits
                );
            }

            $qbCount = clone($qb);
            $hits = $qbCount->count()->getQuery()->execute();

            $skip = $fields['skip'];
            $amount = $fields['amount'] > 100 ? 100 : $fields['amount'];
            $qb->skip($skip)->limit($amount);

            if ($fields['sort']) {
                $qb->sort($fields['sort'], $fields['order']);
            }

            $query = $qb->getQuery();
            $suggestions = $query->execute();


            /** @var \App\Document\Content $suggestion */
            foreach ($suggestions as $suggestion) {
                $suggestionFields = $suggestion->getFields();

                switch ($fields['format']) {
                    case 'short':
                        $this->lastItems[] = isset($suggestionFields['title']['value']) ? $suggestionFields['title']['value'] : '';
                        break;
                    case 'full':
                        $this->lastItems[] = $suggestion->toArray();
                        break;
                    default:
                        $this->lastItems[] = [
                            'id' => $suggestion->getId(),
                            'nid' => $suggestion->getNid(),
                            'agency' => $suggestion->getAgency(),
                            'title' => isset($suggestionFields['title']['value']) ? $suggestionFields['title']['value'] : '',
                            'changed' => isset($suggestionFields['changed']['value']) ? $suggestionFields['changed']['value'] : '',
                        ];
                }
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
     * Insert a new menu entry.
     *
     * @Route("/menu", methods={"PUT"})
     * @OA\Put(
     *     description="",
     *     tags={"Menu"}
     * )
     */
    public function menuCreateAction(Request $request)
    {
        return $this->menuDispatcher($request);
    }

    /**
     * Update a menu entry.
     *
     * @Route("/menu", methods={"POST"})
     * @OA\Post(
     *     description="",
     *     tags={"Menu"}
     * )
     */
    public function menuUpdateAction(Request $request)
    {
        return $this->menuDispatcher($request);
    }

    /**
     * Delete a menu entry.
     *
     * @Route("/menu", methods={"DELETE"})
     * @OA\Delete(
     *     description="",
     *     tags={"Menu"}
     * )
     */
    public function menuDeleteAction(Request $request)
    {
        return $this->menuDispatcher($request);
    }

    /**
     * Dispatcher menu related requests.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menuDispatcher(Request $request, ManagerRegistry $dm)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $rmr = new RestMenuRequest($dm);

        return $this->relay($rmr);
    }

    /**
     * Fetch menu items.
     *
     * @Route("/menu/fetch", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Menu"}
     * )
     */
    public function menuFetchAction(Request $request, ManagerRegistry $dm)
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

        $restMenuRequest = new RestMenuRequest($dm);

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
     * Create a new list entry.
     *
     * @Route("/list", methods={"PUT"})
     * @OA\Put(
     *     description="",
     *     tags={"List"}
     * )
     */
    public function listCreateAction(Request $request)
    {
        return $this->listDispatcher($request);
    }

    /**
     * Update a list entry,
     *
     * @Route("/list", methods={"POST"})
     * @OA\Post(
     *     description="",
     *     tags={"List"}
     * )
     */
    public function listUpdateAction(Request $request)
    {
        return $this->listDispatcher($request);
    }

    /**
     * Delete a list entry.
     *
     * @Route("/list", methods={"DELETE"})
     * @OA\Delete(
     *     description="",
     *     tags={"List"}
     * )
     */
    public function listDeleteAction(Request $request)
    {
        return $this->listDispatcher($request);
    }

    /**
     * Dispatcher list related requests.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listDispatcher(Request $request, ManagerRegistry $dm)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $rlr = new RestListsRequest($dm);

        return $this->relay($rlr);
    }

    /**
     * Fetch list entries.
     *
     * @Route("/list/fetch", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"List"}
     * )
     */
    public function listFetchAction(Request $request, ManagerRegistry $dm)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
            'amount' => 10,
            'skip' => 0,
            'promoted' => 1,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        if (-1 !== $fields['promoted']) {
            $fields['promoted'] = filter_var($fields['promoted'], FILTER_VALIDATE_BOOLEAN);
        }

        $restListsRequest = new RestListsRequest($dm);

        $hits = 0;

        if (!$restListsRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            unset($fields['agency']);
            unset($fields['key']);

            try {
                /** @var Lists[] $suggestions */
                $suggestions = call_user_func_array([$restListsRequest, 'fetchLists'], $fields);

                foreach ($suggestions as $suggestion) {
                    $this->lastItems[] = [
                        'lid' => $suggestion->getLid(),
                        'agency' => $suggestion->getAgency(),
                        'name' => $suggestion->getName(),
                        'type' => $suggestion->getType(),
                        'promoted' => $suggestion->getPromoted(),
                        'weight' => $suggestion->getWeight(),
                        'criteria' => $suggestion->getCriteria(),
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
     * Fetch vocabularies for a certain content type.
     *
     * @Route("/taxonomy/vocabularies/{contentType}", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Taxonomy"},
     *     deprecated=true
     * )
     */
    public function taxonomyAction(Request $request, $contentType, ManagerRegistry $dm)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $rtr = new RestTaxonomyRequest($dm);

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
     * Fetch vocabularies for a certain content type.
     *
     * @Route("/taxonomy/vocabularies", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Taxonomy"}
     * )
     */
    public function taxonomyNewAction(Request $request)
    {
        $response = $this->forward(
            'App:Rest:taxonomy',
            [
                'request' => $request,
                'contentType' => $request->query->get('contentType'),
            ]
        );

        return $response;
    }

    /**
     * Fetch terms for a certain vocabulary and content type.
     *
     * @Route("/taxonomy/terms/{vocabulary}/{contentType}/{query}", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Taxonomy"},
     *     deprecated=true
     * )
     */
    public function taxonomySearchAction(Request $request, $vocabulary, $contentType, $query, ManagerRegistry $dm)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $rtr = new RestTaxonomyRequest($dm);

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
     * Fetch terms for a certain vocabulary and content type.
     *
     * @Route("/taxonomy/terms", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Taxonomy"}
     * )
     */
    public function taxonomySearchNewAction(Request $request)
    {
        $response = $this->forward(
            'App:Rest:taxonomySearch',
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
     * Insert a new configuration entry.
     *
     * @Route("/configuration", methods={"PUT"})
     * @OA\Put(
     *     description="",
     *     tags={"Configuration"}
     * )
     */
    public function configurationCreateAction(Request $request)
    {
        return $this->configurationDispatcher($request);
    }

    /**
     * Update a configuration entry.
     *
     * @Route("/configuration", methods={"POST"})
     * @OA\Post(
     *     description="",
     *     tags={"Configuration"}
     * )
     */
    public function configurationUpdateAction(Request $request)
    {
        return $this->configurationDispatcher($request);
    }

    /**
     * Delete a configuration entry.
     *
     * @Route("/configuration", methods={"DELETE"})
     * @OA\Delete(
     *     description="",
     *     tags={"Configuration"}
     * )
     */
    public function configurationDeleteAction(Request $request)
    {
        return $this->configurationDispatcher($request);
    }

    /**
     * Fetch a configuration entry.
     *
     * @Route("/configuration", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Configuration"}
     * )
     */
    public function configurationFetchAction(Request $request, ManagerRegistry $dm)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'key' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        $restConfigurationRequest = new RestConfigurationRequest($dm);

        if (!$restConfigurationRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            unset($fields['key']);
            try {
                /** @var Configuration[] $items */
                $items = call_user_func_array([$restConfigurationRequest, 'getConfiguration'], $fields);
                $settings = [];
                foreach ($items as $k => $item) {
                    $settings[$item->getAgency()] = $item->getSettings();
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function configurationDispatcher(Request $request, ManagerRegistry $dm)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $restContentRequest = new RestConfigurationRequest($dm);

        return $this->relay($restContentRequest);
    }

    /**
     * Processes incoming requests, except for the ones sent with GET method.
     *
     * @param \App\Rest\RestBaseRequest $genericRequest
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function relay(RestBaseRequest $genericRequest, LoggerInterface $logger)
    {
        try {
            $genericRequest->setRequestBody($this->rawContent);
            $result = $genericRequest->handleRequest($this->lastMethod);
            $this->lastMessage = $result;
            $this->lastStatus = true;
        } catch (RestException $exc) {
            $this->lastMessage = "Request fault with exception: '{$exc->getMessage()}'";
        } catch (\Exception $exc) {
            $this->lastMessage = "Generic fault with exception: '{$exc->getMessage()}'";

            $logger->error($exc->getMessage() . "|" . $exc->getFile() . "|" . $exc->getLine());
        }

        return $this->setResponse($this->lastStatus, $this->lastMessage);
    }

    /**
     * Prepares an http response.
     *
     * @param bool $status
     *   Request processed status.
     * @param string $message
     *   Debug message, if any.
     * @param array $items
     *   Response items, if any.
     * @param int $hits
     *   Number of available items.
     *
     * @return \Symfony\Component\HttpFoundation\Response
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
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
