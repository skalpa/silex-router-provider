<?php

namespace Skalpa\Silex\Symfony\Routing\Tests\Fixtures;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class BarController
{
    /**
     * @Route("/bar", name = "bar")
     */
    public function barAction()
    {
        return new Response('barAction result');
    }
}
