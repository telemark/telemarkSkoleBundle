<?php

namespace tfk\telemarkSkoleBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class tfktelemarkSkoleExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('parameters.yml');
    }

    /**
     * Loads Bundle configuration.
     *
     * @param ContainerBuilder $container
     */
    public function prepend( ContainerBuilder $container )
    {
        $configFile = __DIR__ . '/../Resources/config/override.yml';
        $config = Yaml::parse( file_get_contents( $configFile ) );
        $container->prependExtensionConfig( 'ezpublish', $config );
        $container->addResource( new FileResource( $configFile ) );

        $configFile = __DIR__ . '/../Resources/config/image_variations.yml';
        $config = Yaml::parse( file_get_contents( $configFile ) );
        $container->prependExtensionConfig( 'ezpublish', $config );
        $container->addResource( new FileResource( $configFile ) );

        $configFile = __DIR__ . '/../Resources/config/liip_image_variations.yml';
        $config = Yaml::parse( file_get_contents( $configFile ) );
        $container->prependExtensionConfig( 'liip_imagine', $config );
        $container->addResource( new FileResource( $configFile ) );

        $configFile = __DIR__ . '/../Resources/config/layouts.yml';
        $config = Yaml::parse(file_get_contents($configFile));
        //$container->prependExtensionConfig('ez_systems_landing_page_field_type', $config);
        $container->prependExtensionConfig('ezplatform_page_fieldtype', $config);
        $container->addResource(new FileResource($configFile));

        /*
        $configFile = __DIR__ . '/../Resources/config/liip_image_variations.yml';
        $config = Yaml::parse( file_get_contents( $configFile ) );
        $container->prependExtensionConfig( 'liip_imagine', $config );
        $container->addResource( new FileResource( $configFile ) );
        */
    }

}
