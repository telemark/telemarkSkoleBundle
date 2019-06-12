<?php

declare(strict_types=1);

namespace tfk\telemarkSkoleBundle\Event;

use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Renderer\BlockRenderEvents;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Renderer\Event\PreRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DummyBlockListener implements EventSubscriberInterface
{
    public function __construct()
    {}

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            BlockRenderEvents::getBlockPreRenderEventName('dummy') => 'onBlockPreRender',
        ];
    }


    public function onBlockPreRender( PreRenderEvent $event ): void
    {
        $blockValue = $event->getBlockValue();
        $renderRequest = $event->getRenderRequest();

        $parameters = $renderRequest->getParameters();
        //$string = var_dump( $blockValue );
        $parameters['dummy_string'] = 'foobar';

        $renderRequest->setParameters($parameters);
    }
}

