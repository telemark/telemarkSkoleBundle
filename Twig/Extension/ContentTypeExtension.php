<?php

namespace tfk\telemarkSkoleBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;

class ContentTypeExtension extends \Twig_Extension
{    

    private $container;

    public function setContainer($container) 
    {
        $this->container = $container;
        $this->repository = $container->get('ezpublish.api.repository');
    } 

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('getContentTypeIdentifier', array($this, 'getContentTypeIdentifier' ) )
        );
    }

    public function getContentTypeIdentifier( $id )
    {
        $contentTypeService = $this->repository->getContentTypeService();
        try
        {
            $contentType = $contentTypeService->loadContentType( $id );
            return $contentType->identifier;
        }
        catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
        {
            //return;
        }    
        return false;
    }

    public function getName()
    {
        return 'content_type_extension';
    }
}