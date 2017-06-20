<?php

namespace Skalpa\Silex\Symfony\Routing\Tests\Fixtures;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FooController
{
    /**
     * @Route("/foo", name = "foo")
     */
    public function fooAction()
    {
        return new Response('fooAction result');
    }

    /**
     * @Route("/foo/invoke", name = "foo_invoke")
     */
    public function __invoke()
    {
        return new Response('fooInvoke result');
    }
}
