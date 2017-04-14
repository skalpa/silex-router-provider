<?php

namespace Skalpa\Silex\Symfony\Tests\Fixtures;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class FooController
{
    /**
     * @Route("/foo", name = "foo")
     */
    public function fooAction()
    {
        return new Response('fooAction result');
    }
}
