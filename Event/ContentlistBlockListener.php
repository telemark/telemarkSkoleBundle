<?php

declare(strict_types=1);

namespace tfk\telemarkSkoleBundle\Event;

use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Renderer\BlockRenderEvents;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Renderer\Event\PreRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;


class ContentlistBlockListener implements EventSubscriberInterface
{
    private $contentService;
    private $locationService;
    private $searchService;

    public function __construct( ContentService $contentService, LocationService $locationService, SearchService $searchService )
    {
        $this->contentService   = $contentService;
        $this->locationService  = $locationService;
        $this->searchService    = $searchService;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            BlockRenderEvents::getBlockPreRenderEventName('contentlist') => 'onBlockPreRender',
        ];
    }


    public function onBlockPreRender( PreRenderEvent $event ): void
    {
        $blockValue = $event->getBlockValue();
        $renderRequest = $event->getRenderRequest();

        $parameters = $renderRequest->getParameters();
        $locationListAttribute = $blockValue->getAttribute('locationlist');

        $parameters['block_value'] = $blockValue;

        $contentId      = $blockValue->getAttribute( 'contentId' )->getValue();
        $limit          = (int)$blockValue->getAttribute( 'limit' )->getValue();
        $contentTypes   = $blockValue->getAttribute( 'contentType' )->getValue();

        $parentContent  = $this->contentService->loadContent( $contentId );
        $parentLocation = $this->locationService->loadLocation( $parentContent->contentInfo->mainLocationId );

        $query          = new LocationQuery();
        $query->limit   = $limit;
        $query->offset  = 0;

        $arrCriteria[]  = new Criterion\ParentLocationId( $parentLocation->id );
        $arrCriteria[]  = new Criterion\ContentTypeIdentifier( explode( ',', $contentTypes ) );
        $arrCriteria[]  = new Criterion\Visibility( Criterion\Visibility::VISIBLE );

        $query->query  = new Criterion\LogicalAnd( $arrCriteria );

        $query->sortClauses = $parentLocation->getSortClauses();
        $result = $this->searchService->findLocations( $query );

        foreach ( $result->searchHits as $hit )
        {
            $items[] = array( 
                'content'   => $this->contentService->loadContent( $hit->valueObject->contentInfo->id ),
                'location'  => $hit->valueObject
            );

        }
        $parameters['contentArray'] = $items;


        $renderRequest->setParameters($parameters);
    }
}

