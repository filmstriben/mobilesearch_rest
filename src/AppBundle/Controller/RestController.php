<?php
/**
 * @file
 */

namespace AppBundle\Controller;

use AppBundle\Document\Content;
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
     *  description="Persists an entry of content.",
     *  section="Content",
     *  requirements={}
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
     *  description="Updates an existing entry of content.",
     *  section="Content",
     *  requirements={}
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
     *  description="Deletes an entry of content.",
     *  section="Content",
     *  requirements={}
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
     * @param Request $request
     * @return Response
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
     * @ApiDoc(
     *  description="Fetches content entries.",
     *  section="Content",
     *  requirements={}
     * )
     * @Route("/content/fetch")
     * @Method({"GET"})
     */
    public function contentFetchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
            'agency' => null,
            'key' => null,
            'node' => null,
            'amount' => 10,
            'skip' => 0,
            'sort' => 'fields.title.value',
            'order' => 'ASC',
            'type' => null,
            'status' => RestContentRequest::STATUS_ALL,
        );

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
                    $this->lastItems[] = array(
                        'id' => $item->getId(),
                        'nid' => $item->getNid(),
                        'agency' => $item->getAgency(),
                        'type' => $item->getType(),
                        'fields' => $item->getFields(),
                        'taxonomy' => $item->getTaxonomy(),
                        'list' => $item->getList(),
                    );
                }

                $this->lastStatus = true;
            }
        }

        return $this->setResponse($this->lastStatus, $this->lastMessage, $this->lastItems);
    }

    /**
     * @ApiDoc(
     *  description="Searches content entries by certain criteria(s).",
     *  section="Content",
     *  requirements={}
     * )
     * @Route("/content/search")
     * @Method({"GET"})
     */
    function searchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
            'agency' => null,
            'key' => null,
            'field' => null,
            'query' => null,
        );

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $restContentRequest = new RestContentRequest($em);

        if (!$restContentRequest->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (!empty($fields['query'])) {
            $this->lastItems = array();

            try {
                $suggestions = $restContentRequest->fetchSuggestions(
                    $fields['agency'],
                    (array)$fields['query'],
                    (array)$fields['field']
                );

                foreach ($suggestions as $suggestion) {
                    $fields = $suggestion->getFields();
                    $this->lastItems[] = array(
                        'id' => $suggestion->getId(),
                        'nid' => $suggestion->getNid(),
                        'title' => isset($fields['title']['value']) ? $fields['title']['value'] : '',
                        'changed' => isset($fields['changed']['value']) ? $fields['changed']['value'] : '',
                    );
                }

                $this->lastStatus = true;
            }
            catch (RestException $e) {
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
     *  description="Persists a menu entry.",
     *  section="Menu",
     *  requirements={}
     * )
     * @Route("/menu")
     * @Method({"PUT"})
     */
    public function menuCreateAction(Request $request) {
        return $this->menuDispatcher($request);
    }

    /**
     * @ApiDoc(
     *  description="Updates a menu entry.",
     *  section="Menu",
     *  requirements={}
     * )
     * @Route("/menu")
     * @Method({"POST"})
     */
    public function menuUpdateAction(Request $request) {
        return $this->menuDispatcher($request);
    }

    /**
     * @ApiDoc(
     *  description="Deletes a menu entry.",
     *  section="Menu",
     *  requirements={}
     * )
     * @Route("/menu")
     * @Method({"DELETE"})
     */
    public function menuDeleteAction(Request $request) {
        return $this->menuDispatcher($request);
    }

    /**
     * Dispatcher menu related requests.
     *
     * @param Request $request  Incoming Request object.
     *
     * @return Response         Outgoing Response object.
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
     *  description="Persists a list entry.",
     *  section="List",
     *  requirements={}
     * )
     * @Route("/list")
     * @Method({"PUT"})
     */
    public function listCreateAction(Request $request) {
        return $this->listDispatcher($request);
    }

    /**
     * @ApiDoc(
     *  description="Updates a list entry.",
     *  section="List",
     *  requirements={}
     * )
     * @Route("/list")
     * @Method({"POST"})
     */
    public function listUpdateAction(Request $request) {
        return $this->listDispatcher($request);
    }

    /**
     * @ApiDoc(
     *  description="Deletes a list entry.",
     *  section="List",
     *  requirements={}
     * )
     * @Route("/list")
     * @Method({"DELETE"})
     */
    public function listDeleteAction(Request $request) {
        return $this->listDispatcher($request);
    }

    /**
     * Dispatcher list related requests.
     *
     * @param Request $request  Incoming Request object.
     *
     * @return Response         Outgoing Response object.
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
     *  description="Fetches vocabularies for a certain content entry type.",
     *  section="Taxonomy",
     *  requirements={}
     * )
     * @Route("/taxonomy/vocabularies/{contentType}")
     * @Method({"GET"})
     */
    public function taxonomyAction(Request $request, $contentType)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
            'agency' => null,
            'key' => null
        );

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
     * @ApiDoc(
     *  description="Fetches term suggestions matching the query.",
     *  section="Taxonomy",
     *  requirements={}
     * )
     * @Route("/taxonomy/terms/{vocabulary}/{contentType}/{query}")
     * @Method({"GET"})
     */
    public function taxonomySearchAction(Request $request, $vocabulary, $contentType, $query)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
            'agency' => null,
            'key' => null
        );

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
     * @ApiDoc(
     *  description="Fetches content entries related to certain vocabulary terms.",
     *  section="Content",
     *  requirements={}
     * )
     * @Route("/content/related")
     * @Method({"GET"})
     */
    public function taxonomyRelatedContentAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
            'agency' => null,
            'key' => null,
            'vocabulary' => null,
            'terms' => null
        );

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rtr = new RestTaxonomyRequest($em);

        if (!$rtr->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } else {
            $items = $rtr->fetchRelatedContent(
                $fields['agency'],
                (array)$fields['vocabulary'],
                (array)$fields['terms']
            );
            $this->lastItems = array();

            if (!empty($items)) {
                foreach ($items as $item) {
                    $this->lastItems[] = array(
                        'id' => $item->getId(),
                        'nid' => $item->getNid(),
                        'agency' => $item->getAgency(),
                        'type' => $item->getType(),
                        'fields' => $item->getFields(),
                        'taxonomy' => $item->getTaxonomy(),
                        'list' => $item->getList()
                    );
                }
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
     * Processes incoming requests, except for the ones sent with GET method.
     *
     * @param RestBaseRequest $genericRequest   Generic request object wrapper.
     *
     * @return Response                         Outgoing Response object.
     */
    private function relay(RestBaseRequest $genericRequest)
    {
        try {
            $genericRequest->setRequestBody($this->rawContent);
            $result = $genericRequest->handleRequest($this->lastMethod);
            $this->lastMessage = $result;
            $this->lastStatus = true;
        } catch (RestException $exc) {
            $this->lastMessage = 'Request fault: ' . $exc->getMessage();
        } catch (\Exception $exc) {
            $this->lastMessage = 'Generic fault: ' . $exc->getMessage();
        }

        $response = $this->setResponse($this->lastStatus, $this->lastMessage);

        return $response;
    }

    /**
     * Prepares an http response.
     *
     * @param bool $status      Request processed status.
     * @param string $message   Debug message, if any.
     * @param array $items      Response items, if any.
     *
     * @return Response         Outgoing Response object.
     */
    private function setResponse($status = true, $message = '', $items = array())
    {
        $responseContent = array(
            'status' => $status,
            'message' => $message,
            'items' => $items,
        );

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
