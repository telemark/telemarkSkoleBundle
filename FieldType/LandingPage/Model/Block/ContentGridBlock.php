<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace tfk\telemarkSkoleBundle\FieldType\LandingPage\Model\Block;

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

/**
 * ContentListBlock block
 * Displays list of content from given root.
 */
class ContentGridBlock extends AbstractBlockType implements BlockType
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

        $query = new Query();
        $query->query = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId($contentInfo->mainLocationId),
                new Criterion\ContentTypeIdentifier(strstr($attributes['contentType'], ',') ? explode(',', $attributes['contentType']) : $attributes['contentType']),
                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            ]
        );

        if (isset($attributes['limit']) && ($attributes['limit'] > 1)) {
            $query->limit = (int) $attributes['limit'];
        }

        $searchHits = $this->searchService->findContent($query)->searchHits;

        $contentArray = [];
        foreach ($searchHits as $key => $searchHit) {
            $content = $searchHit->valueObject;
            $locationId = $this->contentService->loadContentInfo($content->id)->mainLocationId;
            $location = $this->locationService->loadLocation($locationId);
            $contentArray[$key]['content'] = $content;
            $contentArray[$key]['location'] = $location;
        }

        return [
            'name'          => $blockValue->getName(),
            'contentArray'  => $contentArray,
            'viewType'      => $attributes['viewType'],
            'block'         => $blockValue,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockDefinition()
    {
        return new BlockDefinition(
            'contentgrid',
            'Content grid',
            'default',
            'bundles/ezsystemslandingpagefieldtype/images/thumbnails/contentlist.svg',
            [],
            [
                new BlockAttributeDefinition(
                    'contentId',
                    'Parent',
                    'embed',
                    self::PATTERN_CONTENT_ID,
                    'Choose a Content item'
                ),
                new BlockAttributeDefinition(
                    'limit',
                    'Limit',
                    'integer',
                    '/[0-9]+/',
                    'Provide a number'
                ),
                new BlockAttributeDefinition(
                    'viewType',
                    'View type',
                    'select',
                    self::PATTERN_CONTENT_TYPE,
                    'Choose one view type',
                    true,
                    false,
                    [],
                    [
                        'float2' => '2 i bredden',
                        'float3' => '3 i bredden',
                    ]
                ),
                new BlockAttributeDefinition(
                    'contentType',
                    'ContentTypes to be displayed',
                    'multiple',
                    self::PATTERN_CONTENT_TYPE,
                    'Choose at least one content type',
                    true,
                    false,
                    [],
                    [
                        'article' => 'Article'
                    ]
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

        if (isset($attributes['limit']) && ((!is_numeric($attributes['limit'])) || ($attributes['limit'] < 1))) {
            throw new InvalidBlockAttributeException('Content grid', 'limit', 'Limit must be a number greater than 0.');
        }

        if ( !isset($attributes['viewType']) || $attributes['viewType'] == '' ) {
            throw new InvalidBlockAttributeException('Content grid', 'viewType', 'View type must be selected.');
        }

        if (!isset($attributes['contentType']) || preg_match(self::PATTERN_CONTENT_TYPE, $attributes['contentType']) !== 1) {
            throw new InvalidBlockAttributeException('Content grid', 'contentType', 'At least one existing ContentType name must be defined.');
        }
    }
}
