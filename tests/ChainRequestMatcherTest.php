<?php

namespace Skalpa\Silex\Symfony\Tests;

use PHPUnit\Framework\TestCase;
use Silex\Provider\Routing\RedirectableUrlMatcher;
use Skalpa\Silex\Symfony\Routing\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ChainRequestMatcherTest extends TestCase
{
    /** @var  RequestContext */
    private $context;
    /** @var  RequestMatcherInterface */
    private $fooMatcher;
    /** @var  RequestMatcherInterface */
    private $bazMatcher;

    protected function setUp()
    {
        $this->context = (new RequestContext())->setPathInfo('/test/');

        $routes1 = new RouteCollection();
        $routes1->add('foo_route', (new Route('/test/foo', ['_controller' => 'fooAction']))->setMethods('GET'));
        $this->fooMatcher = new RedirectableUrlMatcher($routes1, $this->context);

        $routes2 = new RouteCollection();
        $routes2->add('baz_route', (new Route('/test/baz', ['_controller' => 'bazAction']))->setMethods('GET'));
        $routes1->add('foo_route_post', (new Route('/test/foo', ['_controller' => 'fooAction']))->setMethods('POST'));
        $this->bazMatcher = new RedirectableUrlMatcher($routes2, $this->context);
    }

    public function testMatchingFirstInChain()
    {
        $chain = new ChainRequestMatcher([$this->fooMatcher, $this->bazMatcher]);

        $request = Request::create('/test/foo');
        $this->assertSame(['_controller' => 'fooAction', '_route' => 'foo_route'], $chain->matchRequest($request));
    }

    public function testMatchingLastInChain()
    {
        $chain = new ChainRequestMatcher([$this->fooMatcher, $this->bazMatcher]);

        $request = Request::create('/test/baz');
        $this->assertSame(['_controller' => 'bazAction', '_route' => 'baz_route'], $chain->matchRequest($request));
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\MethodNotAllowedException
     */
    public function testFailIfMethodIsNotAllowed()
    {
        $chain = new ChainRequestMatcher([$this->fooMatcher, $this->bazMatcher]);

        $request = Request::create('/test/baz', 'POST');
        // The matcher retrieves the method from the RequestContext and not the request itself
        $this->context->setMethod('POST');
        $chain->matchRequest($request);
    }

    public function testDoesNotFailImmediatelyIfMethodIsNotAllowed()
    {
        $chain = new ChainRequestMatcher([$this->fooMatcher, $this->bazMatcher]);

        $request = Request::create('/test/foo');
        $this->context->setMethod('POST');
        $this->assertSame(['_controller' => 'fooAction', '_route' => 'foo_route_post'], $chain->matchRequest($request));
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testFailIfRouteIsNotMatched()
    {
        $chain = new ChainRequestMatcher([$this->fooMatcher, $this->bazMatcher]);

        $request = Request::create('invalid');
        $chain->matchRequest($request);
    }
}
