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

#use tfk\telemarkSkoleBundle\Helper\SortLocationClauseHelper;

class MenuController extends Controller
{

    public function mainMenuAction()
    {
        $rootLocation = $this->getRootLocation();
        $configResolver = $this->getConfigResolver();
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $identifiers        = $configResolver->getParameter( 'identifiers', 'topmenu' );
        $hideIdentifiers    = $configResolver->getParameter( 'identifiers_hide_from_menu', 'topmenu' );
     
        $items = array();

        $query = new LocationQuery();

        if ( $hideIdentifiers )
        {
            $arr1Criteria[] = new Criterion\ParentLocationId( $rootLocation->id );
            $arr1Criteria[] = new Criterion\ContentTypeIdentifier( $hideIdentifiers );
            $arr1Criteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );
            $arr1Criteria[] = new Criterion\Field( "hide_from_menu", Criterion\Operator::EQ, false );

            $arrCriteria[]  = new Criterion\LogicalAnd($arr1Criteria);
        }

        if ( $identifiers )
        {
            $arr2Criteria[] = new Criterion\ParentLocationId( $rootLocation->id );
            $arr2Criteria[] = new Criterion\ContentTypeIdentifier( $identifiers );
            $arr2Criteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );

            $arrCriteria[]  = new Criterion\LogicalAnd($arr2Criteria);
        }

        $query->filter  = new Criterion\LogicalOr( $arrCriteria );

        #$sorting = new SortLocationClauseHelper();
        #$sortingClause = $sorting->getSortClauseFromLocation( $rootLocation );

        $query->sortClauses = $rootLocation->getSortClauses();
        $result = $searchService->findLocations( $query );

        foreach ( $result->searchHits as $hit )
        {
            $items[] = $hit->valueObject;

        }

        $menuItems = array();

        // second level of main menu
        foreach( $items as $item )
        {
            $location = $locationService->loadLocation($item->id);
            $query = new LocationQuery();
            unset( $arrCriteria );
            unset( $arr1Criteria );
            unset( $arr2Criteria );

            if ( $hideIdentifiers )
            {
                $arr1Criteria[] = new Criterion\ParentLocationId( $item->id );
                $arr1Criteria[] = new Criterion\ContentTypeIdentifier( $hideIdentifiers );
                $arr1Criteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );
                $arr1Criteria[] = new Criterion\Field( "hide_from_menu", Criterion\Operator::EQ, false );

                $arrCriteria[]  = new Criterion\LogicalAnd($arr1Criteria);
            }

            if ( $identifiers )
            {
                $arr2Criteria[] = new Criterion\ParentLocationId( $item->id );
                $arr2Criteria[] = new Criterion\ContentTypeIdentifier( $identifiers );
                $arr2Criteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );

                $arrCriteria[]  = new Criterion\LogicalAnd($arr2Criteria);
            }

            $query->filter  = new Criterion\LogicalOr( $arrCriteria );

            #$sorting = new SortLocationClauseHelper();
            #$sortingClause = $sorting->getSortClauseFromLocation( $location );

            $query->sortClauses = $location->getSortClauses();
            $subResult = $searchService->findLocations( $query );

            $subItems = array();

            foreach ( $subResult->searchHits as $hit )
            {
                $subItems[] = $hit->valueObject;
            }

            $menuItems[] = array(
                'location' => $item,
                'subLevelItemCount' => count($subItems),
                'subLevelItems' => $subItems
            );
        }



        $response = new Response();
        $response->headers->set( 'X-Location-Id', $rootLocation->id );
        $response->setSharedMaxAge( 3600 );
        $response->setVary( 'X-User-Hash' );

        $identifiers = array_merge( $identifiers, $hideIdentifiers );
        return $this->render(
            'tfktelemarkSkoleBundle:menu:main_menu.html.twig',
            array(
                'menuItems' => $menuItems,
                'contentTypesIdentifiers' => $this->getMenuIdentifierIds( $identifiers )
            ),
            $response
        );
    }

    public function getMenuIdentifierIds( $identifiers )
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $contentTypesIdentifierIds = array();
        foreach ( $identifiers as $identifier )
        {
            try
            {
                $contentType = $contentTypeService->loadContentTypeByIdentifier( $identifier );
                $contentTypesIdentifierIds[$contentType->id] = $identifier;
            }
            catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
            {
                //return;
            }
        }
        return $contentTypesIdentifierIds;
    }

}
