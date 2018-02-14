<?php
namespace tfk\telemarkSkoleBundle\FieldType\LandingPage\Model\Block;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\LandingPageFieldTypeBundle\Exception\InvalidBlockAttributeException;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Definition\BlockDefinition;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Definition\BlockAttributeDefinition;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\AbstractBlockType;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\BlockType;
use EzSystems\LandingPageFieldTypeBundle\FieldType\LandingPage\Model\BlockValue;

//use tfk\telemarkSkoleBundle\Helper\CalendarHelper;
//use tfk\telemarkSkoleBundle\Controller\Calendar;


/**
 * ContentListBlock block
 * Displays list of content from given root.
 */
class CalendarBlock extends AbstractBlockType implements BlockType
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

    /** @var ConfigResolverInterface */
    private $configResolver;

    /**
     * @param ConfigResolverInterface   $configResolver
     */
    public function __construct( ConfigResolverInterface $configResolver )
    {
        $this->configResolver = $configResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateParameters( BlockValue $blockValue )
    {
        $attributes = $blockValue->getAttributes();

        if (isset($attributes['limit']) && ($attributes['limit'] > 1)) {
            $query->limit = (int) $attributes['limit'];
        }

        //setLocale(LC_TIME, 'no_NO');
        //$today  = new DateTime( date("Y-m-d") );
        //$endDay = new DateTime( date('Y-m-d', strtotime( "+30 days" ) ) );
        //$calendarFolders = $this->findCalendarFolders();
        //$upcomingEvents  = $this->findEvents( $today, $endDay, $calendarFolders );
        $upcomingEvents = array( 'a', 'b' );

        return [
            'name'          => $blockValue->getName(),
            'contentArray'  => $upcomingEvents,
            'block'         => $blockValue,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockDefinition()
    {
        return new BlockDefinition(
            'calendar',
            'Calendar',
            'default',
            'bundles/ezsystemslandingpagefieldtype/images/thumbnails/contentlist.svg',
            [],
            [
                new BlockAttributeDefinition(
                    'limit',
                    'Limit',
                    'integer',
                    '/[0-9]+/',
                    'Provide a number'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function checkAttributesStructure(array $attributes)
    {
        if (isset($attributes['limit']) && ((!is_numeric($attributes['limit'])) || ($attributes['limit'] < 1))) {
            throw new InvalidBlockAttributeException('Content grid', 'limit', 'Limit must be a number greater than 0.');
        }
    }
}
