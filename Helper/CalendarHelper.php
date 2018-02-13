<?php
/**
 * Created by PhpStorm.
 * User: toremi
 * Date: 01.09.2017
 * Time: 12.33
 */

namespace tfk\telemarkSkoleBundle\Helper;


use eZ\Publish\Core\MVC\ConfigResolverInterface;
use jamesiarmes\PhpEws\Client;

class CalendarHelper
{
    private $configResolver;

    function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    public function setupClient( $setTimeZone = false )
    {
        $host     = $this->configResolver->getParameter( 'host', 'ews' );
        $username = $this->configResolver->getParameter( 'username', 'ews' );
        $password = $this->configResolver->getParameter( 'password', 'ews' );
        $timezone = $this->configResolver->getParameter( 'timezone', 'ews' );

        $client = new Client($host, $username, $password, Client::VERSION_2010);
        if ($setTimeZone)
            $client->setTimezone( $timezone );
        
        return $client;
    }
}