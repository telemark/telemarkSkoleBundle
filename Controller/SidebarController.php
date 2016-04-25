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

class SidebarController extends Controller
{
    public function sidebarItemsAction( $locationId )
    {
        $locationService = $this->getRepository()->getLocationService();     
        $configResolver = $this->getConfigResolver();

        $identifiers = $configResolver->getParameter( 'identifiers.right', 'sidebar' );

        $location = $locationService->loadLocation( $locationId );
        $result = $this->get( 'vp_utility.location_helper' )->getLocationItems( $location, $identifiers, true );

        $response = new Response();
        $response->headers->set( 'X-Location-Id', $locationId );
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $this->render(
            'tfktelemarkSkoleBundle:parts:sidebar_right.html.twig', 
            array(
                "items" => $result['items']
            ),
            $response
        );        
    }

}

?>
