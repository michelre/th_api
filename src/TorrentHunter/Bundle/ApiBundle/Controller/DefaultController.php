<?php

namespace ApiBundle\Controller;

use CrawlerBundle\CrawlerBundle;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use JMS\DiExtraBundle\Annotation as DI;


class DefaultController
{

    protected $solrService;
    protected $crawlerTrackersService;

    /**
     * @DI\InjectParams({
     *     "solrService" = @DI\Inject("solrService"),
     *     "mainController" = @DI\Inject("main_controller")
     * })
     */
    public function __construct(\SolrServiceBundle\Controller\DefaultController $solrService, \CrawlerBundle\Controller\DefaultController $mainController){
        $this->solrService = $solrService;
        $this->crawlerTrackersService = $mainController;
    }

    /**
     * @Route("/api/{version}", name="API specification")
     * @Method("GET")
     */
    public function indexAction()
    {
        $spec = array(
            array("url" => "/api/{version}/torrents/top",
                "method" => "GET",
                "description" => "Retrieve the most popular torrents files"),
            array("url" => "/api/{version}/search?query={string}[&offset={int}&limit={int}]",
                  "method" => "GET",
                  "description" => "Make a search to solr collection"),
            array("url" => "/api/{version}/torrent/{tracker}/{slug}",
                "method" => "GET",
                "description" => "Retrieve detail for a specific torrent"),
            array("url" => "/api/{version}/search/similar/{slug}",
                "method" => "GET",
                "description" => "Retrieve all documents that are similar to a specific file"),
            array("url" => "/api/{version}/torrents/{category}[?offset={int}&limit={int}]",
                "method" => "GET",
                "description" => "Retrieve torrents per categories"),
            array("url" => "/api/{version}/torrent/{tracker}/details/{slug}",
                "method" => "GET",
                "description" => "Retrieve torrent's details")
        );

        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        return new Response($serializer->serialize($spec, 'json'));
    }

    /**
     * @Route("/api/{version}/torrents/top", name="API popular torrents")
     * @Method("GET")
     */
    public function popularAction($version, Request $request){
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        return new Response($serializer->serialize($this->solrService->popularAction(), 'json'));
    }

    /**
     * @Route("/api/{version}/search", name="API search")
     * @Method("GET")
     */
    public function searchAction($version, Request $request){
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();

        $query = $request->query->get("query");
        $offset = ($request->query->get("offset")) ? $request->query->get("offset") : 1;
        $limit = ($request->query->get("limit")) ? $request->query->get("limit") : 10;

        return new Response($serializer->serialize($this->solrService->searchAction($query, $limit, $offset), 'json'));
    }

    /**
     * @Route("/api/{version}/torrent/{tracker}/{slug}", name="API torrent details")
     * @Method("GET")
     */
    public function torrentAction($version, $tracker, $slug){
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();

        return new Response($serializer->serialize($this->solrService->torrentAction($tracker, $slug), 'json'));
    }

    /**
     * @Route("/api/{version}/search/similar/{slug}", name="API similar search")
     * @Method("GET")
     */
    public function searchSimilarAction($version, $slug){
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();

        return new Response($serializer->serialize($this->solrService->searchSimilarAction($slug), 'json'));
    }

    /**
     * @Route("/api/{version}/torrents/{category}", name="API search per categories")
     * @Method("GET")
     */
    public function torrentsByCategoryAction($version, $category, Request $request){
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();

        $offset = ($request->query->get("offset")) ? $request->query->get("offset") : 1;
        $limit = ($request->query->get("limit")) ? $request->query->get("limit") : 10;

        return new Response($serializer->serialize(array("torrents" => $this->solrService->torrentsByCategoryAction($category, $offset, $limit), "category" => $category), 'json'));
    }

    /**
     * @Route("/api/{version}/stats", name="API get stats for one tracker")
     * @Method("GET")
     */
    public function statsTrackersAction($version){
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        return new Response($serializer->serialize($this->solrService->statsTrackersAction(), 'json'));
    }

    /**
     * @Route("/api/{version}/torrent/{tracker}/details/{slug}", name="Retrieve details of a specific torrent")
     * @Method("GET")
     */
    public function torrentDetail($version, $tracker, $slug){
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();

        $torrent = $this->solrService->torrentAction($tracker, $slug);
        $details = $this->crawlerTrackersService->getTorrentDetail($tracker, $torrent['url']);

        return new Response($serializer->serialize($details, 'json'));
    }
}
