<?php

namespace Skalpa\Silex\Symfony\Tests;

use PHPUnit\Framework\TestCase;
use Skalpa\Silex\Symfony\Routing\ChainUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ChainUrlGeneratorTest extends TestCase
{
    /** @var  RequestContext */
    private $context;
    /** @var  UrlGenerator */
    private $fooGenerator;
    /** @var  UrlGenerator */
    private $bazGenerator;

    protected function setUp()
    {
        $this->context = (new RequestContext())->setPathInfo('/test/');

        $routes1 = new RouteCollection();
        $routes1->add('foo_route', new Route('/test/foo'));
        $this->fooGenerator = new UrlGenerator($routes1, $this->context);

        $routes2 = new RouteCollection();
        $routes2->add('baz_route', new Route('/test/baz'));
        $this->bazGenerator = new UrlGenerator($routes2, $this->context);
    }

    public function testContextIsPassedToGenerators()
    {
        $chain = new ChainUrlGenerator([$this->fooGenerator, $this->bazGenerator], $this->context);

        $this->assertSame($this->context, $chain->getContext());
        $this->assertSame($this->context, $this->fooGenerator->getContext());
        $this->assertSame($this->context, $this->bazGenerator->getContext());
    }

    public function testGeneratingUrlFromFirstInChain()
    {
        $chain = new ChainUrlGenerator([$this->fooGenerator, $this->bazGenerator], $this->context);

        $this->assertSame('foo', $chain->generate('foo_route', [], UrlGeneratorInterface::RELATIVE_PATH));
    }

    public function testGeneratingUrlFromLastInChain()
    {
        $chain = new ChainUrlGenerator([$this->bazGenerator, $this->fooGenerator], $this->context);

        $this->assertSame('foo', $chain->generate('foo_route', [], UrlGeneratorInterface::RELATIVE_PATH));
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testFailIfRouteIsNotFound()
    {
        $chain = new ChainUrlGenerator([$this->fooGenerator, $this->bazGenerator], $this->context);

        $chain->generate('invalid');
    }

    public function testRequestContextCanBeChanged()
    {
        $chain = new ChainUrlGenerator([$this->fooGenerator, $this->bazGenerator], $this->context);
        $chain->setContext($context = (new RequestContext())->setPathInfo('/test/'));

        $this->assertSame($context, $chain->getContext());
        $this->assertSame($context, $this->fooGenerator->getContext());
        $this->assertSame($context, $this->bazGenerator->getContext());
    }
}
