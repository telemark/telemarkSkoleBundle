<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace tfk\telemarkSkoleBundle\FieldType\LandingPage\Model\Block;

use EzSystems\LandingPageFieldTypeBundle\Exception\InvalidBlockAttributeException;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Definition\BlockDefinition;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Definition\BlockAttributeDefinition;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\AbstractBlockType;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\BlockType;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\BlockValue;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

/**
 * ContentBox block
 * Renders content from given location.
 */
class ContentBox3x1Block extends AbstractBlockType implements BlockType
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

    /** @var ConfigResolverInterface */
    private $configResolver;

    /**
     * @param LocationService           $locationService
     * @param ContentService            $contentService
     * @param SearchService             $searchService
     * @param ConfigResolverInterface   $configResolver
     */
    public function __construct(LocationService $locationService, ContentService $contentService, SearchService $searchService, ConfigResolverInterface $configResolver)
    {
        $this->locationService = $locationService;
        $this->contentService  = $contentService;
        $this->searchService   = $searchService;
        $this->configResolver  = $configResolver;
    }


    /**
     * {@inheritdoc}
     */
    public function getTemplateParameters(BlockValue $blockValue)
    {
        $languages = array( 'languages' => $this->configResolver->getParameter( 'languages' ) );

        $attributes = $blockValue->getAttributes();

    
        if (  isset( $attributes['content1_Order'] ) || isset( $attributes['content2_Order'] ) || isset( $attributes['content3_Order'] ) || isset( $attributes['content4_Order'] ) )
            $ordering = true;
        else
            $ordering = false;

        $dataArray    = array();
        $contentArray = array();
        $listArray    = array();

        if ( isset( $attributes['content1_Id'] ) )
        {
            $order = ( isset( $attributes['content1_Order'] ) ? $attributes['content1_Order'] : 0  );
            try
            {
                $contentInfo = $this->contentService->loadContentInfo($attributes['content1_Id']);
                if ( $ordering )
                    $dataArray[] = array( 
                        'locationId' => $contentInfo->mainLocationId,
                        'order'    => $order
                    );
                else
                    $contentArray[] = $contentInfo->mainLocationId;
            }
            catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
            {
                //return;
            } 
        }
            
        if ( isset( $attributes['content2_Id'] ) )
        {
            $order = ( isset( $attributes['content2_Order'] ) ? $attributes['content2_Order'] : 0  );
            try
            {
                $contentInfo = $this->contentService->loadContentInfo($attributes['content2_Id']);
                if ( $ordering )
                    $dataArray[] = array( 
                        'locationId' => $contentInfo->mainLocationId,
                        'order'    => $order
                    );
                else
                    $contentArray[] = $contentInfo->mainLocationId;
            }
            catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
            {
                //return;
            } 
        }

        if ( isset( $attributes['content3_Id'] ) )
        {
            $order = ( isset( $attributes['content3_Order'] ) ? $attributes['content3_Order'] : 0  );
            try
            {
                $contentInfo = $this->contentService->loadContentInfo($attributes['content3_Id']);
                if ( $ordering )
                    $dataArray[] = array( 
                        'locationId' => $contentInfo->mainLocationId,
                        'order'    => $order
                    );
                else
                    $contentArray[] = $contentInfo->mainLocationId;
            }
            catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
            {
                //return;
            } 
        }

        if ( isset( $attributes['content4_Id'] ) )
        {
            $order = ( isset( $attributes['content4_Order'] ) ? $attributes['content4_Order'] : 0  );
            try
            {
                $contentInfo = $this->contentService->loadContentInfo($attributes['content4_Id']);
                if ( $ordering )
                    $dataArray[] = array( 
                        'locationId' => $contentInfo->mainLocationId,
                        'order'    => $order
                    );
                else
                    $contentArray[] = $contentInfo->mainLocationId;
            }
            catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
            {
                //return;
            } 
        }

        // get list
        if ( isset( $attributes['content5_Id'] ) )
        {
            try
            {
                $contentInfo = $this->contentService->loadContentInfo($attributes['content5_Id']);
                $query = new Query();
                $query->query = new Criterion\LogicalAnd(
                    [
                        new Criterion\ParentLocationId($contentInfo->mainLocationId),
                        new Criterion\Visibility(Criterion\Visibility::VISIBLE),
                    ]
                );

                $result = $this->searchService->findContent($query, $languages);
                foreach ($result->searchHits as $searchHit)
                    $listArray[] = $searchHit->valueObject;
            }
            catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
            {
                //return;
            } 
        }

        // do sorting of items based on order numbers
        if ( $ordering )
        {
            foreach ( $dataArray as $key => $row )
            {
                $locationArray[$key] = $row[ 'locationId' ];
                $orderArray[$key]    = $row[ 'order' ];
            }

            array_multisort( $orderArray, SORT_ASC, $dataArray );

            foreach ( $dataArray as $data )
                $contentArray[] = $data[ 'locationId' ];
        }


        return [
            'contentArray' => $contentArray,
            'listArray'    => $listArray
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockDefinition()
    {
        return new BlockDefinition(
            'contentbox3x1',
            'Content box 3x1',
            'default',
            'bundles/ezsystemslandingpagefieldtype/images/thumbnails/embed.svg',
            [],
            [
                new BlockAttributeDefinition(
                    'content1_Id',
                    'Content',
                    'embed',
                    '/^([a-zA-Z]:)?(\/[a-zA-Z0-9_\/-]+)+\/?/',
                    'Choose a Content item',
                    false
                ),
                new BlockAttributeDefinition(
                    'content1_Order',
                    'Order',
                    'integer',
                    self::PATTERN_CONTENT_ID,
                    'Order must be an integer',
                    false
                ),
                new BlockAttributeDefinition(
                    'content2_Id',
                    'Content',
                    'embed',
                    '/^([a-zA-Z]:)?(\/[a-zA-Z0-9_\/-]+)+\/?/',
                    'Choose a Content item',
                    false
                ),
                new BlockAttributeDefinition(
                    'content2_Order',
                    'Order',
                    'integer',
                    self::PATTERN_CONTENT_ID,
                    'Order must be an integer',
                    false
                ),
                new BlockAttributeDefinition(
                    'content3_Id',
                    'Content',
                    'embed',
                    '/^([a-zA-Z]:)?(\/[a-zA-Z0-9_\/-]+)+\/?/',
                    'Choose a Content item',
                    false
                ),
                new BlockAttributeDefinition(
                    'content3_Order',
                    'Order',
                    'integer',
                    self::PATTERN_CONTENT_ID,
                    'Order must be an integer',
                    false
                ),
                new BlockAttributeDefinition(
                    'content4_Id',
                    'Content',
                    'embed',
                    '/^([a-zA-Z]:)?(\/[a-zA-Z0-9_\/-]+)+\/?/',
                    'Choose a Content item',
                    false
                ),
                new BlockAttributeDefinition(
                    'content4_Order',
                    'Order',
                    'integer',
                    self::PATTERN_CONTENT_ID,
                    'Order must be an integer',
                    false
                ),
                new BlockAttributeDefinition(
                    'content5_Id',
                    'Content list',
                    'embed',
                    '/^([a-zA-Z]:)?(\/[a-zA-Z0-9_\/-]+)+\/?/',
                    'Choose a Content item',
                    false
                ),
                /*
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
                        'manual-row'  => 'En rekke',
                        'manual-grid' => '2 x 2',
                    ]
                ),
                */
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function checkAttributesStructure(array $attributes)
    {
        /*
        if ( !isset($attributes['content1_Id']) ) {
            throw new InvalidBlockAttributeException('Content box', 'contentId', 'Content ID must be set.');
        }
        */

        if ( isset($attributes['content1_Id']) &&!is_numeric($attributes['content1_Id']) ) {
            throw new InvalidBlockAttributeException('Content box', 'contentId', 'Content ID must be type of integer.');
        }

        if ( isset($attributes['content2_Id']) &&!is_numeric($attributes['content2_Id']) ) {
            throw new InvalidBlockAttributeException('Content box', 'contentId', 'Content ID must be type of integer.');
        }

        if ( isset($attributes['content3_Id']) &&!is_numeric($attributes['content3_Id']) ) {
            throw new InvalidBlockAttributeException('Content box', 'contentId', 'Content ID must be type of integer.');
        }

        if ( isset($attributes['content4_Id']) &&!is_numeric($attributes['content4_Id']) ) {
            throw new InvalidBlockAttributeException('Content box', 'contentId', 'Content ID must be type of integer.');
        }

        if ( isset($attributes['content5_Id']) &&!is_numeric($attributes['content5_Id']) ) {
            throw new InvalidBlockAttributeException('Content box', 'contentId', 'Content ID must be type of integer.');
        }

    }
}
