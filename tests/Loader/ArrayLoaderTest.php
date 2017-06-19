<?php

namespace Skalpa\Silex\Symfony\Routing\Tests\Loader;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Skalpa\Silex\Symfony\Routing\Loader\ArrayLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ArrayLoaderTest extends TestCase
{
    public function testLoaderIsLazy()
    {
        $called = false;
        $container = new Container();
        $container['loader'] = function () use (&$called) {
            $called = true;

            return $this->getMockBuilder(LoaderInterface::class)->getMock();
        };
        new ArrayLoader($container, 'loader');

        $this->assertFalse($called);
    }

    /**
     * @dataProvider provideForwardedMethods
     */
    public function testMethodsAreForwarded($testedMethod, $expectedArguments, $returnValue)
    {
        $loader = $this->getMockBuilder(LoaderInterface::class)->getMock();
        $loader->expects($this->once())
            ->method($testedMethod)
            ->with(...$expectedArguments)
            ->willReturn($returnValue);

        $container = new Container([
            'loader' => function () use ($loader) {
                return $loader;
            },
        ]);
        $proxy = new ArrayLoader($container, 'loader');
        $result = $proxy->$testedMethod(...$expectedArguments);

        $this->assertSame($returnValue, $result);
    }

    public function provideForwardedMethods()
    {
        return [
            ['getResolver', [], $this->getMockBuilder(LoaderResolverInterface::class)->getMock()],
            ['setResolver', [$this->getMockBuilder(LoaderResolverInterface::class)->getMock()], true],
            ['load', ['foo.yml', 'yml'], new RouteCollection()],
            ['supports', ['foo.yml', 'yml'], true],
        ];
    }

    public function testSupportsReturnsTrueIfAllResourcesAreSupported()
    {
        $loader = $this->getMockBuilder(LoaderInterface::class)->getMock();
        $loader->expects($this->exactly(2))
            ->method('supports')
            ->withConsecutive(['foo.yml', null], ['bar.yml', 'yml'])
            ->willReturnOnConsecutiveCalls(true, true);

        $container = new Container([
            'loader' => function () use ($loader) {
                return $loader;
            },
        ]);
        $proxy = new ArrayLoader($container, 'loader');

        $this->assertTrue($proxy->supports([0 => 'foo.yml', 'bar.yml' => 'yml']));
    }

    public function testSupportsReturnsFalseIfOneResourceIsNotSupported()
    {
        $loader = $this->getMockBuilder(LoaderInterface::class)->getMock();
        $loader->expects($this->exactly(2))
            ->method('supports')
            ->withConsecutive(['foo.yml', null], ['bar.yml', 'yml'])
            ->willReturnOnConsecutiveCalls(true, false);

        $container = new Container([
            'loader' => function () use ($loader) {
                return $loader;
            },
        ]);
        $proxy = new ArrayLoader($container, 'loader');

        $this->assertFalse($proxy->supports([0 => 'foo.yml', 'bar.yml' => 'yml']));
    }

    public function testCanLoadArrayOfResources()
    {
        $collection1 = new RouteCollection();
        $collection1->add('foo', $fooRoute = new Route('/foo'));
        $collection2 = new RouteCollection();
        $collection2->add('bar', $barRoute = new Route('/bar'));

        $loader = $this->getMockBuilder(LoaderInterface::class)->getMock();
        $loader->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(['foo.yml', null], ['bar.yml', null])
            ->willReturnOnConsecutiveCalls($collection1, $collection2);

        $container = new Container([
            'loader' => function () use ($loader) {
                return $loader;
            },
        ]);
        $proxy = new ArrayLoader($container, 'loader');
        /** @var RouteCollection $result */
        $result = $proxy->load(['foo.yml', 'bar.yml']);

        $this->assertSame($fooRoute, $result->get('foo'));
        $this->assertSame($barRoute, $result->get('bar'));
    }
}
