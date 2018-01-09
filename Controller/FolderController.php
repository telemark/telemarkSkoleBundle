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

        $page = intval( $request->query->get('page') );
        if ( $page < 1 )
            $page = 1;

        if ( $configResolver->hasParameter( 'identifiers', 'subitems') )
            $identifiers = $configResolver->getParameter( 'identifiers', 'subitems' );
        if ( $configResolver->hasParameter( 'limit', 'subitems') )
            $limit = $configResolver->getParameter( 'limit', 'subitems' );

        //$limit = $rows * $columns;
        $offset = $this->get( 'vp_utility.pagination_helper' )->getOffsetFromPage( $page, $limit );

        $location = $locationService->loadLocation( $locationId );
        $result = $this->get( 'vp_utility.location_helper' )->getLocationItems( $location, $identifiers, true, $limit, $offset );

        $pagination = $this->get( 'vp_utility.pagination_helper' )->paginate( $result['totalCount'], $limit, $offset, $page );
        $params += array(
            'subitems'  => $result['items'],
            'totalCount' => $result['totalCount'],
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'maxPage' => $pagination['maxPage'],
            'prevPage' => $pagination['prevPage'],
            'nextPage' => $pagination['nextPage']
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


        if ( $configResolver->hasParameter( 'newsfolder.identifiers', 'subitems') )
            $identifiers = $configResolver->getParameter( 'newsfolder.identifiers', 'subitems' );
        if ( $configResolver->hasParameter( 'newsfolder.rows', 'subitems') )
            $rows = $configResolver->getParameter( 'newsfolder.rows', 'subitems' );
        if ( $configResolver->hasParameter( 'newsfolder.columns', 'subitems') )
            $columns = $configResolver->getParameter( 'newsfolder.columns', 'subitems' );

        $limit = $rows * $columns;
        $limit = 25;
        $offset = $this->get( 'vp_utility.pagination_helper' )->getOffsetFromPage( $page, $limit );

        $location = $locationService->loadLocation( $locationId );
        $result = $this->get( 'vp_utility.location_helper' )->getLocationItems( $location, $identifiers, true, $limit, $offset );

        $pagination = $this->get( 'vp_utility.pagination_helper' )->paginate( $result['totalCount'], $limit, $offset, $page );
        $params += array(
            'items'  => $result['items'],
            'totalCount' => $result['totalCount'],
            'columns' => $columns,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'maxPage' => $pagination['maxPage'],
            'prevPage' => $pagination['prevPage'],
            'nextPage' => $pagination['nextPage']
        );

        $response = $this->get( 'ez_content' )->viewLocation( $locationId, $viewType, $layout, $params );
        $response->headers->set( 'X-Location-Id',  $locationId );
        $response->setSharedMaxAge( 15 );

        return $response;
    }


}

?>
