<?php

namespace tfk\telemarkSkoleBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;

class ConfigResolverExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction('getConfigResolverParameter', array($this, 'getConfigResolverParameter' ) )
        );
    }

    public function getConfigResolverParameter( $parameter, $section )
    {
        $configResolver = $this->container->get('ezpublish.config.resolver');
        //echo '<p>Resolving: ' . $section . ':' . $parameter .' '. microtime(true).'</p>';

        if ( $configResolver->hasParameter( $parameter, $section ) )
            $result = $configResolver->getParameter( $parameter, $section );
        else
            $result = false;

        return $result;
    }

    public function getName()
    {
        return 'config_resolver_extension';
    }
}