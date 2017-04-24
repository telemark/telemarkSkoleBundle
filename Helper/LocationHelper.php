<?php

namespace tfk\telemarkSkoleBundle\Helper;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use eZ\Publish\Core\Repository\LocationService;
#use tfk\telemarkSkoleBundle\Helper\SortLocationClauseHelper;

class LocationHelper
{
    
    private $repository;
    private $container;
    private $configResolver;

    public function __construct( Repository $repository, Container $container, ConfigResolverInterface $configResolver)
    {
        $this->repository = $repository;
        $this->container = $container;
        $this->configResolver = $configResolver;
    }

    public function getLocationItems( $location, $identifiers=array(), $hide_from_menu=false, $limit=false, $offset=0 )
    {
        $subItemsSorting = false;
        $searchService = $this->repository->getSearchService();

        $query = new LocationQuery();
        if ( $limit )
            $query->limit = (int)$limit;
        $query->offset = (int)$offset;

        // convert string to array
        if ( !is_array( $identifiers ) )
            $identifiers = array( $identifiers );

        $otherIdentifiers = array();
        foreach ( $identifiers as $identifier )
        {
            if ( $identifier != 'folder' )
                $otherIdentifiers[] = $identifier;
        }

        if ( in_array( 'folder', $identifiers ) && $hide_from_menu == true )
        {
            $arr1Criteria[] = new Criterion\ParentLocationId( $location->id );
            $arr1Criteria[] = new Criterion\ContentTypeIdentifier( array( 'folder' ) );
            $arr1Criteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );
            //$arr1Criteria[] = new Criterion\Field( "hide_from_menu", Criterion\Operator::EQ, false );

            $arr2Criteria[] = new Criterion\ParentLocationId( $location->id );
            $arr2Criteria[] = new Criterion\ContentTypeIdentifier( $otherIdentifiers );
            $arr2Criteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );

            $arrCriteria[]  = new Criterion\LogicalAnd($arr1Criteria);
            $arrCriteria[]  = new Criterion\LogicalAnd($arr2Criteria);

            $query->filter  = new Criterion\LogicalOr( $arrCriteria );
        }
        else
        {
            $arrCriteria[] = new Criterion\ParentLocationId( $location->id );
            $arrCriteria[] = new Criterion\ContentTypeIdentifier( $identifiers );
            $arrCriteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );
            
            $query->filter = new Criterion\LogicalAnd($arrCriteria);
        }

        if ( $subItemsSorting == true )
        {
            $query->sortClauses = array(
                new SortClause\Location\Priority( Query::SORT_DESC ),
                new SortClause\DatePublished( Query::SORT_DESC )
            );
        }
        else
        {
            #$sorting = new SortLocationClauseHelper();
            #$sortingClause = $sorting->getSortClauseFromLocation( $location );
            $query->sortClauses = $location->getSortClauses();
        }

        $result = $searchService->findLocations( $query );

        $items = array();
        foreach($result->searchHits as $hit)
        {
            $items[] = $hit->valueObject;
        }

        if ( count( $items ) )
            return array( 
                'success' => true, 
                'items' => $items, 
                'totalCount' => $result->totalCount,
                'limit' => $limit,
                'offset' => $offset
            );
        else
            return array( 'success' => true, 'items' => array(), 'totalCount' => 0 );

    }

 
}