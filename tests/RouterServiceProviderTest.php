<?php

namespace Skalpa\Silex\Symfony\Routing\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Skalpa\Silex\Symfony\Routing\RouterServiceProvider;
use Skalpa\Silex\Symfony\Routing\Tests\Fixtures\FooController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route as RouteAnnotation;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Yaml\Yaml;

class RouterServiceProviderTest extends TestCase
{
    public function setUp()
    {
        if (class_exists(AnnotationReader::class)) {
            AnnotationRegistry::reset();
        }
    }

    public static function skipIfNoDoctrineAnnotations()
    {
        if (!class_exists(AnnotationReader::class)) {
            self::markTestSkipped('The Doctrine Annotation library is required');
        }
    }


    public function getApplication(array $parameters = [])
    {
        $app = new Application();
        $app->register(new RouterServiceProvider(), $parameters);

        return $app;
    }

    public function testRoutesAreLoadedFromYamlResource()
    {
        if (!class_exists(Yaml::class)) {
            $this->markTestSkipped('The Symfony Yaml component is required');
        }

        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.yml',
        ]);

        $this->assertSame(1, $app['router']->getRouteCollection()->count());
    }

    public function testRoutesAreLoadedFromXmlResource()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.xml',
        ]);

        $this->assertSame(1, $app['router']->getRouteCollection()->count());
    }

    public function testRoutesAreLoadedFromPhpResource()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.php',
        ]);

        $this->assertSame(1, $app['router']->getRouteCollection()->count());
    }

    public function testTheFileLocatorPathsParameterSetsResourceRoot()
    {
        $app = $this->getApplication([
            'router.file_locator.paths' => [__DIR__.'/Fixtures'],
            'router.resource' => 'routing.php',
        ]);

        $this->assertSame(1, $app['router']->getRouteCollection()->count());
    }

    public function testOptionsArePassedToTheRouter()
    {
        $app = $this->getApplication([
            'router.debug' => true,
            'router.cache_dir' => __DIR__,
            'router.resource_type' => 'xml',
            'router.options' => [
                'matcher_class' => 'FoobarMatcherClass',
                'matcher_base_class' => 'FoobarMatcherBase',
                'matcher_cache_class' => 'FoobarMatcherCache',
            ],
        ]);
        $router = $app['router'];

        $this->assertTrue($router->getOption('debug'));
        $this->assertSame(__DIR__, $router->getOption('cache_dir'));
        $this->assertSame('xml', $router->getOption('resource_type'));
        $this->assertSame('FoobarMatcherClass', $router->getOption('matcher_class'));
        $this->assertSame('FoobarMatcherBase', $router->getOption('matcher_base_class'));
        $this->assertSame('FoobarMatcherCache', $router->getOption('matcher_cache_class'));
    }

    public function testLoadedRoutesAreMatched()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.php',
        ]);

        $response = $app->handle(Request::create('/foo'));

        $this->assertSame('fooAction result', $response->getContent());
    }

    public function testCanGenerateUrlsOfLoadedRoutes()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.xml',
        ]);

        $this->assertSame('/foo', $app['url_generator']->generate('foo', [], UrlGeneratorInterface::ABSOLUTE_PATH));
    }

    public function testSilexRoutesAreMatched()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.xml',
        ]);
        $app->get('/bar', function () {
            return 'silex bar result';
        });

        $response = $app->handle(Request::create('/bar'));

        $this->assertSame('silex bar result', $response->getContent());
    }

    public function testCanGenerateUrlsOfSilexRoutes()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.xml',
        ]);
        $app->get('/bar', function () {
            return 'silex bar result';
        })->bind('bar');

        $app->flush();

        $this->assertSame('/bar', $app['url_generator']->generate('bar', [], UrlGeneratorInterface::ABSOLUTE_PATH));
    }

    public function testLoadedRoutesTakePrecedenceWhenMatching()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.xml',
        ]);
        $app->get('/foo', function () {
            return 'silex foo result';
        });

        $response = $app->handle(Request::create('/foo'));

        $this->assertSame('fooAction result', $response->getContent());
    }

    public function testLoadedRoutesTakePrecedenceWhenGeneratingUrls()
    {
        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/routing.xml',
        ]);
        $app->get('/silex/foo', function () {
            return 'silex foo result';
        })->bind('foo');

        $app->flush();

        $this->assertSame('/foo', $app['url_generator']->generate('foo'));
    }

    public function testRoutesAreLoadedFromClassAnnotations()
    {
        self::skipIfNoDoctrineAnnotations();

        $app = $this->getApplication([
            'router.resource' => FooController::class,
            'router.resource_type' => 'annotation',
        ]);

        $this->assertSame(2, $app['router']->getRouteCollection()->count());
    }

    public function testRoutesAreLoadedFromFileAnnotations()
    {
        self::skipIfNoDoctrineAnnotations();

        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/FooController.php',
            'router.resource_type' => 'annotation',
        ]);

        $this->assertSame(2, $app['router']->getRouteCollection()->count());
    }

    public function testRoutesAreLoadedFromDirectoryAnnotations()
    {
        self::skipIfNoDoctrineAnnotations();

        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures',
            'router.resource_type' => 'annotation',
        ]);

        $this->assertSame(4, $app['router']->getRouteCollection()->count());
    }

    public function testAnnotationsReaderCanBeServiceName()
    {
        self::skipIfNoDoctrineAnnotations();

        $reader = $this->getMockBuilder(AnnotationReader::class)->setMethods(['getMethodAnnotations'])->getMock();
        $reader->expects($this->any())
            ->method('getMethodAnnotations')
            ->willReturnCallback(function (\ReflectionMethod $method) {
                return [new RouteAnnotation(['value' => '/'.$method->name, 'name' => $method->name])];
            });

        $app = $this->getApplication([
            'router.resource' => __DIR__.'/Fixtures/FooController.php',
            'router.resource_type' => 'annotation',
            'router.annotation.reader' => 'test_reader',
            'test_reader' => function () use ($reader) {
                AnnotationRegistry::registerLoader('class_exists');

                return $reader;
            },
        ]);

        $route = $app['router']->getRouteCollection()->get('fooAction');

        $this->assertSame('/fooAction', $route->getPath());
    }

    public function testAnnotatedRoutesConfigurationWhenControllerIsService()
    {
        self::skipIfNoDoctrineAnnotations();

        $app = $this->getApplication([
            'router.resource' => FooController::class,
            'router.resource_type' => 'annotation',
            FooController::class => function () {
                return new FooController();
            },
        ]);

        /** @var \Symfony\Component\Routing\Route $route */
        $route = $app['router']->getRouteCollection()->get('foo');

        $this->assertSame(FooController::class.':fooAction', $route->getDefault('_controller'));
    }

    public function testAnnotatedRoutesConfigurationWhenControllerIsNotService()
    {
        self::skipIfNoDoctrineAnnotations();

        $app = $this->getApplication([
            'router.resource' => FooController::class,
            'router.resource_type' => 'annotation',
        ]);

        /** @var \Symfony\Component\Routing\Route $route */
        $route = $app['router']->getRouteCollection()->get('foo');

        $this->assertSame(FooController::class.'::fooAction', $route->getDefault('_controller'));
    }

    public function testAnnotatedRoutesConfigurationWhenControllerIsInvokable()
    {
        self::skipIfNoDoctrineAnnotations();

        $app = $this->getApplication([
            'router.resource' => FooController::class,
            'router.resource_type' => 'annotation',
        ]);

        /** @var \Symfony\Component\Routing\Route $route */
        $route = $app['router']->getRouteCollection()->get('foo_invoke');

        $this->assertSame(FooController::class, $route->getDefault('_controller'));
    }

    public function testAnnotatedRoutesWithCustomConfigurationStrategy()
    {
        self::skipIfNoDoctrineAnnotations();

        $app = $this->getApplication([
            'router.resource' => FooController::class,
            'router.resource_type' => 'annotation',
        ]);
        $app['router.annotation.configuration_strategy'] = $app->protect(function (Route $route, \ReflectionClass $class, \ReflectionMethod $method) {
            $route->setDefault('_controller', str_replace('/', '', $route->getPath()).'-'.str_replace('\\', '_', $class->name).'-'.$method->name);
        });

        /** @var Route $route */
        $route = $app['router']->getRouteCollection()->get('foo');

        $this->assertSame('foo-'.str_replace('\\', '_', FooController::class).'-fooAction', $route->getDefault('_controller'));
    }
}
