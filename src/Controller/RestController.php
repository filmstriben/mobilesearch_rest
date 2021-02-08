<?php

namespace App\Controller;

use App\Ast\Walker\MongoTreeWalker;
use App\Document\Configuration;
use App\Document\Content;
use App\Document\Lists;
use App\Document\Menu;
use App\Exception\RestException;
use App\Repositories\ListsRepository;
use App\Rest\RestBaseRequest;
use App\Rest\RestConfigurationRequest;
use App\Rest\RestContentRequest;
use App\Rest\RestListsRequest;
use App\Rest\RestMenuRequest;
use App\Rest\RestTaxonomyRequest;
//use Doctrine\MongoDB\Query\Expr;
use Nelmio\ApiDocBundle\Annotation\Model as ApiDoc;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


final class RestController extends AbstractController
{
    private $lastStatus = false;
    private $lastMessage = '';
    private $lastMethod;
    private $lastItems = [];
    private $rawContent;

    /**
     * @Route("/content", methods={"PUT"})
     */
    public function contentCreateAction(Request $request)
    {
        return $this->contentDispatcher($request);
    }

    /**
     * @Route("/content", methods={"POST"})
     */
    public function contentUpdateAction(Request $request)
    {
        return $this->contentDispatcher($request);
    }

    /**
     * @Route("/content", methods={"DELETE"})
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
     * @Route("/content/fetch", methods={"GET"})
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
     * @Route("/content/search", methods={"GET"})
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

                /** @var \AppBundle\Document\Content $suggestion */
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
     * @Route("/content/search-ranked", methods={"GET"})
     *
     * TODO: Test coverage.
     */
    public function contentSearchRankedAction(Request $request)
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

        $em = $this->get('doctrine_mongodb');
        $restContentRequest = new RestContentRequest($em);

        $hits = 0;

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (!empty($fields['q'])) {
            $em = $this->get('doctrine_mongodb');
            /** @var \AppBundle\Repositories\ContentRepository $contentRepository */
            $contentRepository = $em->getRepository(Content::class);
            $suggestions = $contentRepository->fetchSuggestions(
                $fields['q'],
                $fields['amount'],
                $fields['skip']
            );

            /** @var \AppBundle\Document\Content $suggestion */
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
     * @Route("/content/search-extended", methods={"GET"})
     *
     * TODO: Test coverage.
     */
    public function searchExtendedAction(Request $request)
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

        $em = $this->get('doctrine_mongodb');
        $restContentRequest = new RestContentRequest($em);

        $hits = 0;

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (!empty($fields['q'])) {
            unset($fields['agency'], $fields['key']);

            /** @var \Doctrine\ODM\MongoDB\Query\Builder $qb */
            $qb = $this
                ->get('doctrine_mongodb')
                ->getManager()
                ->createQueryBuilder(Content::class);

            $query = $fields['q'];
            $parser = $this->get('query_parser');

            try {
                $ast = $parser->parse($query);
                $treeWalker = new MongoTreeWalker($qb);
                $ast->transform($treeWalker);
            } catch (\RuntimeException $exception) {
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->get('logger');
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


            /** @var \AppBundle\Document\Content $suggestion */
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
     * @Route("/menu", methods={"PUT"})
     */
    public function menuCreateAction(Request $request)
    {
        return $this->menuDispatcher($request);
    }

    /**
     * @Route("/menu", methods={"POST"})
     */
    public function menuUpdateAction(Request $request)
    {
        return $this->menuDispatcher($request);
    }

    /**
     * @Route("/menu", methods={"DELETE"})
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
     * @Route("/menu/fetch", methods={"GET"})
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
     * @Route("/list", methods={"PUT"})
     */
    public function listCreateAction(Request $request)
    {
        return $this->listDispatcher($request);
    }

    /**
     * @Route("/list", methods={"POST"})
     */
    public function listUpdateAction(Request $request)
    {
        return $this->listDispatcher($request);
    }

    /**
     * @Route("/list", methods={"DELETE"})
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
     * @Route("/list/fetch", methods={"GET"})
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
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = null !== $request->query->get($field) ? $request->query->get($field) : $fields[$field];
        }

        if (-1 !== $fields['promoted']) {
            $fields['promoted'] = filter_var($fields['promoted'], FILTER_VALIDATE_BOOLEAN);
        }

        $em = $this->get('doctrine_mongodb');
        $restListsRequest = new RestListsRequest($em);

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
     * @Route("/taxonomy/vocabularies/{contentType}", methods={"GET"})
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
     * @Route("/taxonomy/vocabularies", methods={"GET"})
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
     * @Route("/taxonomy/terms/{vocabulary}/{contentType}/{query}", methods={"GET"})
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
     * @Route("/taxonomy/terms", methods={"GET"})
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
     * @Route("/configuration", methods={"PUT"})
     */
    public function configurationCreateAction(Request $request)
    {
        return $this->configurationDispatcher($request);
    }

    /**
     * @Route("/configuration", methods={"POST"})
     */
    public function configurationUpdateAction(Request $request)
    {
        return $this->configurationDispatcher($request);
    }

    /**
     * @Route("/configuration", methods={"DELETE"})
     */
    public function configurationDeleteAction(Request $request)
    {
        return $this->configurationDispatcher($request);
    }

    /**
     * @Route("/configuration", methods={"GET"})
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
            $this->lastMessage = "Request fault with exception: '{$exc->getMessage()}'";
        } catch (\Exception $exc) {
            $this->lastMessage = "Generic fault with exception: '{$exc->getMessage()}'";

            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->error($exc->getMessage() . "|" . $exc->getFile() . "|" . $exc->getLine());
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
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
