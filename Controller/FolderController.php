<?php

namespace tfk\telemarkSkoleBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\Pagination\Pagerfanta\ContentSearchAdapter;
use Pagerfanta\Pagerfanta;
use eZ\Publish\API\Repository\Values\Content\Location;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use tfk\telemarkBundle\Helper\SortLocationHelper;

class FolderController extends Controller
{
    public function folderViewEnhancedAction( $locationId, $viewType, $layout = false, array $params = array() )
    {
        $configResolver = $this->getConfigResolver();
        $locationService = $this->getRepository()->getLocationService();


        $request = Request::createFromGlobals();
        $scriptUri = $request->server->get('SCRIPT_URI');
        $searchString = $request->query->get('SearchText'); 
        $offset = 0;
        if ( $configResolver->hasParameter( 'identifiers', 'subitems') )
            $identifiers = $configResolver->getParameter( 'identifiers', 'subitems' );
        if ( $configResolver->hasParameter( 'limit', 'subitems') )
            $limit = $configResolver->getParameter( 'limit', 'subitems' );

        $location = $locationService->loadLocation( $locationId );
        $result = $this->get( 'vp_utility.location_helper' )->getLocationItems( $location, $identifiers, true, $limit, $offset );

        $params += array( 
            'subitems'  => $result['items'],
            'totalCount' => $result['totalCount']
        );

        $response = $this->get( 'ez_content' )->viewLocation( $locationId, $viewType, $layout, $params );
        $response->headers->set( 'X-Location-Id',  $locationId );
        $response->setSharedMaxAge( 15 );

        return $response;
    }

    public function newsfolderViewEnhancedAction( $locationId, $viewType, $layout = false, array $params = array() )
    {
        // get request data for pagination, month, year
        
        $configResolver = $this->getConfigResolver();
        $locationService = $this->getRepository()->getLocationService();
        $request = Request::createFromGlobals();
        
        $page = intval( $request->query->get('page') );
        if ( $page < 1 )
            $page = 1;


        // load subitems with pagination

        if ( $configResolver->hasParameter( 'newsfolder.identifiers', 'subitems') )
            $identifiers = $configResolver->getParameter( 'newsfolder.identifiers', 'subitems' );
        if ( $configResolver->hasParameter( 'newsfolder.rows', 'subitems') )
            $rows = $configResolver->getParameter( 'newsfolder.rows', 'subitems' );
        if ( $configResolver->hasParameter( 'newsfolder.columns', 'subitems') )
            $columns = $configResolver->getParameter( 'newsfolder.columns', 'subitems' );

        $limit = $rows * $columns;
        $limit = 3;
        if ( isset( $page ) )
            $offset = ( $page - 1 ) * $limit;
        else
            $offset = 0;

        $location = $locationService->loadLocation( $locationId );
        $result = $this->get( 'vp_utility.location_helper' )->getLocationItems( $location, $identifiers, true, $limit, $offset );

        if ( $result['totalCount'] > 0 )
        {
            if ( intval( $result['totalCount'] / $limit ) < $result['totalCount'] / $limit )
                $maxPage = intval( $result['totalCount'] / $limit ) + 1;
            else
                $maxPage = intval( $result['totalCount'] / $limit );
            $prevPage = false;
            $nextPage = false;

            $page = $offset / $limit + 1;
            if ( $page > $maxPage )
                $prevPage = $maxPage;
            elseif ( $offset > 0 )
            {
                $prevPage = $page - 1;
            }
            if ( $page * $limit < $result['totalCount'] )
            {
                $nextPage = $page + 1;
            }
        }
        else
        {
            $maxPage = 2;
            $prevPage = 1;
            $nextPage = false;
        }

/*
        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd([
            new Criterion\ParentLocationId( $locationId ),
            new Criterion\ContentTypeIdentifier( $identifiers ),
            new Criterion\Visibility( Criterion\Visibility::VISIBLE )
        ]);
        $query->sortClauses = array(
            new SortClause\DatePublished(),
        );

        // Initialize pagination.
        $pager = new Pagerfanta(
            new ContentSearchAdapter($query, $this->getRepository()->getSearchService())
        );

        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($request->get('page', 1));


        echo '<pre>';
        //var_dump($pager);
        echo '</pre>';
*/

        // get keywords
        // most used or related???
        // get archives overview

        $params += array( 
            'items'  => $result['items'],
            'columns' => $columns,
            //'pagerFolder' => $pager,
            'result' => $result,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'maxPage' => $maxPage,
            'prevPage' => $prevPage,
            'nextPage' => $nextPage,
            'request' => $request
        );

        $response = $this->get( 'ez_content' )->viewLocation( $locationId, $viewType, $layout, $params );
        $response->headers->set( 'X-Location-Id',  $locationId );
        $response->setSharedMaxAge( 15 );

        return $response;
    }


}

?>
