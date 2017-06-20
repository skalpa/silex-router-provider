<?php

namespace Skalpa\Silex\Symfony\Routing\Tests\Fixtures;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BarController
{
    /**
     * @Route("/bar", name = "bar")
     */
    public function barAction()
    {
        return new Response('barAction result');
    }

    /**
     * @Route("/bar/invoke", name = "bar_invoke")
     */
    public function __invoke()
    {
        return new Response('barInvoke result');
    }
}
