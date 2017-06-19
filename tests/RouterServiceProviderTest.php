<?php

namespace Skalpa\Silex\Symfony\Routing\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Skalpa\Silex\Symfony\Routing\RouterServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RouterServiceProviderTest extends TestCase
{
    public function setUp()
    {
        AnnotationRegistry::reset();
    }

    public function getApplication(array $parameters = [])
    {
        $app = new Application();
        $app->register(new RouterServiceProvider(), $parameters);

        return $app;
    }

    public function testWithYamlResource()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.yml',
        ]);
        $response = $app->handle(Request::create('/foo'));

        $this->assertSame('fooAction result', $response->getContent());
    }

    public function testUrlCanGenerateUrls()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.yml',
        ]);

        $this->assertSame('/foo', $app['url_generator']->generate('foo', [], UrlGeneratorInterface::ABSOLUTE_PATH));
    }

    public function testWithXmlResource()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.xml',
        ]);
        $response = $app->handle(Request::create('/foo'));

        $this->assertSame('fooAction result', $response->getContent());
    }

    public function testWithPhpResource()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.php',
        ]);
        $response = $app->handle(Request::create('/foo'));

        $this->assertSame('fooAction result', $response->getContent());
    }

    public function testWithAnnotatedFileResource()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/FooController.php',
            'router.resource_type' => 'annotation',
        ]);
        $response = $app->handle(Request::create('/foo'));

        $this->assertSame('fooAction result', $response->getContent());
    }

    public function testWithAnnotatedDirectoryResource()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures',
            'router.resource_type' => 'annotation',
        ]);
        $fooResponse = $app->handle(Request::create('/foo'));
        $barResponse = $app->handle(Request::create('/bar'));

        $this->assertSame('fooAction result', $fooResponse->getContent());
        $this->assertSame('barAction result', $barResponse->getContent());
    }

    public function testFileLocatorPathsParameterSetsResourceRoot()
    {
        $app = $this->getApplication([
            'router.file_locator.paths' => [__DIR__.'/Fixtures'],
            'router.resource' => 'routing.yml',
        ]);
        $response = $app->handle(Request::create('/foo'));

        $this->assertSame('fooAction result', $response->getContent());
    }

    public function testOptionsArePassedToTheRouter()
    {
        $app = $this->getApplication([
            'router.debug' => true,
            'router.cache_dir' => __DIR__,
            'router.resource_type' => 'xml',
            'router.options' => [
                'matcher_cache_class' => 'FoobarUrlMatcher',
            ],
        ]);
        $router = $app['router'];

        $this->assertTrue($router->getOption('debug'));
        $this->assertSame(__DIR__, $router->getOption('cache_dir'));
        $this->assertSame('xml', $router->getOption('resource_type'));
        $this->assertSame('FoobarUrlMatcher', $router->getOption('matcher_cache_class'));
    }

    public function testAnnotationsReaderCanBeServiceName()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/FooController.php',
            'router.resource_type' => 'annotation',
            'router.annotations_reader' => 'test_reader',
            'test_reader' => function () {
                AnnotationRegistry::registerLoader('class_exists');

                return new AnnotationReader();
            },
        ]);
        $response = $app->handle(Request::create('/foo'));

        $this->assertSame('fooAction result', $response->getContent());
    }
}
