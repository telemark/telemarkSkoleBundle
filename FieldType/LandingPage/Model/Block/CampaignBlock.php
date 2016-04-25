<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace tfk\telemarkSkoleBundle\FieldType\LandingPage\Model\Block;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\SearchService;
use EzSystems\LandingPageFieldTypeBundle\Exception\InvalidBlockAttributeException;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Definition\BlockDefinition;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Definition\BlockAttributeDefinition;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\AbstractBlockType;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\BlockType;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\BlockValue;
//use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\Block;

use tfk\telemarkBundle\Helper\SortLocationHelper;

/**
 * ContentListBlock block
 * Displays list of content from given root.
 */
class CampaignBlock extends AbstractBlockType implements BlockType
{
    /**
     * ContentType regular expression pattern matching single ContentType
     * name or comma separated list of ContentTypes.
     *
     * @example article,place,blog_post
     * @example article
     *
     * @var string
     */
    const PATTERN_CONTENT_TYPE = '/^([a-zA-Z_-]+|,[a-zA-Z_-]+)+$/i';

    /**
     * ContentType ID regular expression.
     *
     * @example 16
     *
     * @var string
     */
    const PATTERN_CONTENT_ID = '/[0-9]+/';

    /** @var LocationService */
    private $locationService;

    /** @var ContentService */
    private $contentService;

    /** @var SearchService */
    private $searchService;

    /**
     * @param LocationService $locationService
     * @param ContentService  $contentService
     * @param SearchService   $searchService
     */
    public function __construct(LocationService $locationService, ContentService $contentService, SearchService $searchService)
    {
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->searchService = $searchService;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateParameters(BlockValue $blockValue)
    {
        $attributes = $blockValue->getAttributes();
        $contentInfo = $this->contentService->loadContentInfo($attributes['contentId']);
        $location = $this->locationService->loadLocation( $contentInfo->mainLocationId );
        $identifiers = array('slide');

        $query = new LocationQuery();
        //$query = new Query();
        $query->query = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId($contentInfo->mainLocationId),
                new Criterion\ContentTypeIdentifier( $identifiers ),
                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            ]
        );

        // sortOrder must be set
        //$sorting = new SortLocationHelper();
        //$sortingClause = $sorting->getSortClauseFromLocation( $location );
        //$query->sortClauses = array($sortingClause);
        
        $result = $this->searchService->findLocations( $query );
        //$result = $this->searchService->findContent( $query );
        $searchHits = $result->searchHits;
        //echo '<pre>';
        //var_dump($searchHits[0]);
        //echo '<pre>';

        $contentArray = array();
        foreach ($searchHits as $searchHit)
        {
            //$content = $searchHit->valueObject;
            $content = $this->contentService->loadContent( $searchHit->valueObject->contentInfo->id );
            $location = $this->locationService->loadLocation( $searchHit->valueObject->id );
            $targetField = $content->getField( 'internal_resource' );

            if ( $targetField->value->destinationContentId )
            {
                $targetContentId = $targetField->value->destinationContentId;
                $targetContent = $this->contentService->loadContent($targetContentId);
                $targetLocation = $this->locationService->loadLocation($targetContent->versionInfo->contentInfo->mainLocationId);
                $contentArray[] = array(
                    'location' => $location, 
                    'content' => $content, 
                    'internal_resource_content' => $targetContent,
                    'internal_resource_location' => $targetLocation
                );
            }
            else
            {
                $contentArray[] = array(
                    'location' => $location, 
                    'content' => $content
                );
            }

        }

        return [
            'name'          => $blockValue->getName(),
            'contentArray'  => $contentArray,
            'block'         => $blockValue,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockDefinition()
    {
        return new BlockDefinition(
            'campaign',
            'Campaign',
            'default',
            'bundles/ezsystemslandingpagefieldtype/images/thumbnails/gallery.svg',
            [],
            [
                new BlockAttributeDefinition(
                    'contentId',
                    'Parent',
                    'embed',
                    self::PATTERN_CONTENT_ID,
                    'Choose a Content item'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function checkAttributesStructure(array $attributes)
    {
        if (!isset($attributes['contentId']) || preg_match(self::PATTERN_CONTENT_ID, $attributes['contentId']) !== 1) {
            throw new InvalidBlockAttributeException('Content grid', 'contentId', 'Parent ContentId must be defined.');
        }
    }
}
