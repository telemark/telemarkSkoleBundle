<?php

namespace tfk\telemarkSkoleBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\Pagination\Pagerfanta\ContentSearchAdapter;
use eZ\Publish\API\Repository\Values\Content\Location;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use tfk\telemarkBundle\Helper\SortLocationHelper;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('tfktelemarkSkoleBundle:Default:index.html.twig', array('name' => $name));
    }

    public function headAction()
    {
        $rootLocation = $this->getRootLocation();
        $configResolver = $this->getConfigResolver();

        if ( $configResolver->hasParameter( 'logo', 'head' ) )
            $logo = $configResolver->getParameter( 'logo', 'head' );

        $response = new Response;
        $response->setSharedMaxAge( 3600 );

        return $this->render(
            'tfktelemarkSkoleBundle::page_head.html.twig',
            array(
                'logo' => $logo
            ),
            $response
        );

    }

    public function mainMenuAction()
    {
        $rootLocation = $this->getRootLocation();
        $configResolver = $this->getConfigResolver();

        $identifiers = $configResolver->getParameter( 'identifiers', 'menu' );
        $items = array();

        $query = new LocationQuery();

        $otherIdentifiers = array();
        foreach ( $identifiers as $identifier )
        {
            if ( $identifier != 'folder' )
                $otherIdentifiers[] = $identifier;
        }

        $arr1Criteria[] = new Criterion\ParentLocationId( $rootLocation->id );
        $arr1Criteria[] = new Criterion\ContentTypeIdentifier( array( 'folder' ) );
        $arr1Criteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );
        $arr1Criteria[] = new Criterion\Field( "hide_from_main_menu", Criterion\Operator::EQ, false );

        $arr2Criteria[] = new Criterion\ParentLocationId( $rootLocation->id );
        $arr2Criteria[] = new Criterion\ContentTypeIdentifier( $otherIdentifiers );
        $arr2Criteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );

        $arrCriteria[]  = new Criterion\LogicalAnd($arr1Criteria);
        $arrCriteria[]  = new Criterion\LogicalAnd($arr2Criteria);

        $query->filter  = new Criterion\LogicalOr( $arrCriteria );

        $sorting = new SortLocationClauseHelper();
        $sortingClause = $sorting->getSortClauseFromLocation( $rootLocation );

        $query->sortClauses = array($sortingClause);
        $result = $searchService->findLocations( $query );

        foreach ( $result->searchHtis as $hit )
        {
            $items[] = $hit->valueObject;
        }

        $response = new Response();
        $response->headers->set( 'X-Location-Id', $rootLocation->id );
        $response->setSharedMaxAge( 3600 );
        $response->setVary( 'X-User-Hash' );

        return $this->render(
            'tfktelemarkSkoleBundle:menu:main_menu.html.twig',
            array(
                'menuItems' => $items
            ),
            $response
        );
    }
}
