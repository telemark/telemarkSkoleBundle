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

class FolderController extends Controller
{
    public function folderViewEnhancedAction( $locationId, $viewType, $layout = false, array $params = array() )
    {
        $configResolver = $this->getConfigResolver();
        $locationService = $this->getRepository()->getLocationService();

        $identifiers = array('article');
        $limit = 10;
        $offset = 0;

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

        // load subitems with pagination

        if ( $configResolver->hasParameter( 'newsfolder.rows', 'subitems') )
            $rows = $configResolver->getParameter( 'newsfolder.rows', 'subitems' );
        if ( $configResolver->hasParameter( 'newsfolder.columns', 'subitems') )
            $columns = $configResolver->getParameter( 'newsfolder.columns', 'subitems' );

        $limit = $rows * $columns;
        $offset = 0;

        $identifiers = array('article');

        $location = $locationService->loadLocation( $locationId );
        $result = $this->get( 'vp_utility.location_helper' )->getLocationItems( $location, $identifiers, true, $limit, $offset );
/*
        echo '<pre>';
        var_dump($result);
        echo '</pre>';
*/


        // get keywords
        // most used or related???
        // get archives overview

        $params += array( 
            'items'  => $result['items'],
            'columns' => $columns
        );

        $response = $this->get( 'ez_content' )->viewLocation( $locationId, $viewType, $layout, $params );
        $response->headers->set( 'X-Location-Id',  $locationId );
        $response->setSharedMaxAge( 15 );

        return $response;
    }


}

?>
