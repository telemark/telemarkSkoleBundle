<?php

namespace tfk\telemarkSkoleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('tfktelemarkSkoleBundle:Default:index.html.twig', array('name' => $name));
    }
}
