<?php

namespace tfk\telemarkSkoleBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\FullText;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\Pagination\Pagerfanta\ContentSearchAdapter;
use eZ\Publish\API\Repository\Values\Content\Location;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use tfk\telemarkBundle\Helper\SortLocationHelper;

class SearchController extends Controller
{
    public function searchAction()
    {
        $searchService = $this->getRepository()->getSearchService();
        $request = Request::createFromGlobals();


        $page = intval( $request->query->get('page') );
        $searchText = $request->query->get('searchText');
        $scriptUri = $request->server->get('SCRIPT_URI');
        $queryString = $request->server->get('QUERY_STRING');      
        $queryUri = $scriptUri.'?'.$queryString;
        $searchUri = $scriptUri.'?searchText='.$searchText;
        $searchText = $request->query->get('searchText');

        if ( intval( $request->query->get('page') ) > 1 )
            $page = intval( $request->query->get('page') );
        else
            $page = 1;

        if ( isset($searchText) )
        {
            $configResolver = $this->getConfigResolver();
            $rootLocation = $this->getRootLocation();
            //$layout = $configResolver->getParameter( 'content_view.viewbase_layout', 'ezpublish' );
            $languages = array( 'languages' => $configResolver->getParameter( 'languages' ) );
            $identifiers = $configResolver->getParameter( 'identifiers', 'search' );
            $limit = $configResolver->getParameter( 'limit', 'search' );

            $offset = $this->get( 'vp_utility.pagination_helper' )->getOffsetFromPage( $page, $limit );

            $query = new Query();
            $query->limit = $limit;
            $query->offset = $offset;

            $query->filter = new Criterion\LogicalAnd([
                new Criterion\ContentTypeIdentifier( $identifiers ),    
                new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
                new Criterion\Subtree( $rootLocation->pathString ),
            ]);

            $query->query = new Criterion\FullText( $searchText );
            
            $result = $searchService->findContent( $query, $languages );

            $pagination = $this->get( 'vp_utility.pagination_helper' )->paginate( $result->totalCount, $limit, $offset, $page );

            $response = new Response();
            $response->headers->addCacheControlDirective('must-revalidate', true);

            return $this->render(
                'tfktelemarkSkoleBundle:search:search.html.twig', 
                array(
                    'layout' => 'tfktelemarkSkoleBundle::pagelayout.html.twig',
                    'noLayout' => false,
                    'result' => $result,
                    'request' => $request,
                    'searchText' => $searchText,
                    'paginationBaseUri' => $searchUri,
                    'page' => $page,
                    'maxPage' => $pagination['maxPage'],
                    'prevPage' => $pagination['prevPage'],
                    'nextPage' => $pagination['nextPage']
                ),
                $response
            );        

        }
        else
        {
            $response = new Response();
            $response->headers->addCacheControlDirective('must-revalidate', true);

            return $this->render(
                'tfktelemarkSkoleBundle:search:search.html.twig', 
                array(
                    'layout' => 'tfktelemarkSkoleBundle::pagelayout.html.twig',
                    'noLayout' => false,
                    'result' => false,
                ),
                $response
            );        

        }
    }
}

?>
