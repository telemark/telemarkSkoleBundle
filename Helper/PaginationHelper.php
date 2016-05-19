<?php

namespace tfk\telemarkSkoleBundle\Helper;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;


class PaginationHelper
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

    public function getOffsetFromPage( $page, $limit )
    {
        if ( isset( $page ) )
            $offset = ( $page - 1 ) * $limit;
        else
            $offset = 0;
        return $offset;
    }

    public function paginate( $totalCount, $limit, $offset, $page )
    {
        if ( $totalCount > 0 )
        {
            if ( intval( $totalCount / $limit ) < $totalCount / $limit )
                $maxPage = intval( $totalCount / $limit ) + 1;
            else
                $maxPage = intval( $totalCount / $limit );
            $prevPage = false;
            $nextPage = false;

            $page = $offset / $limit + 1;
            if ( $page > $maxPage )
                $prevPage = $maxPage;
            elseif ( $offset > 0 )
            {
                $prevPage = $page - 1;
            }
            if ( $page * $limit < $totalCount )
            {
                $nextPage = $page + 1;
            }
        }
        else
        {
            $maxPage = 1;
            $prevPage = false;
            $nextPage = false;
        }

        return array( 
            'page' => $page,
            'maxPage' => $maxPage,
            'prevPage' => $prevPage,
            'nextPage' => $nextPage,

        );
    }

 
}