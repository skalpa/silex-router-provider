<?php

namespace Skalpa\Silex\Symfony\Routing;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\Routing\RedirectableUrlMatcher;
use Skalpa\Silex\Symfony\Routing\Loader\AnnotationClassLoader;
use Skalpa\Silex\Symfony\Routing\Loader\ArrayLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Loader\AnnotationFileLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Router;
use Symfony\Component\Yaml\Yaml;

/**
 * Symfony Routing service provider for Silex.
 */
class RouterServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['router.debug'] = isset($container['debug']) ? $container['debug'] : false;
        $container['router.cache_dir'] = null;
        $container['router.resource'] = null;
        $container['router.resource_type'] = null;
        $container['router.file_locator.paths'] = [];
        $container['router.options'] = [];
        $container['router'] = function (Container $container) {
            $options = array_replace([
                'debug'              => $container['router.debug'],
                'cache_dir'          => $container['router.cache_dir'],
                'resource_type'      => $container['router.resource_type'],
                'matcher_class'      => RedirectableUrlMatcher::class,
                'matcher_base_class' => RedirectableUrlMatcher::class,
            ], $container['router.options']);

            return new Router(
                $container['router.loader'],
                $container['router.resource'],
                $options,
                $container['request_context'],
                $container['logger']
            );
        };

        $container['router.file_locator'] = function (Container $container) {
            return new FileLocator($container['router.file_locator.paths']);
        };
        $container['router.loader.xml'] = function (Container $container) {
            return new XmlFileLoader($container['router.file_locator']);
        };
        $container['router.loader.php'] = function (Container $container) {
            return new PhpFileLoader($container['router.file_locator']);
        };

        $loaders = ['router.loader.xml', 'router.loader.php'];

        if (class_exists(Yaml::class)) {
            $container['router.loader.yaml'] = function (Container $container) {
                return new YamlFileLoader($container['router.file_locator']);
            };
            $loaders[] = 'router.loader.yaml';
        }

        if (class_exists(AnnotationReader::class)) {
            $container['router.annotation.reader'] = function () {
                AnnotationRegistry::registerLoader('class_exists');

                return new CachedReader(new AnnotationReader(), new ArrayCache());
            };

            $container['router.annotation.configuration_strategy'] = $container->protect(function (Route $route, \ReflectionClass $class, \ReflectionMethod $method) use ($container) {
                if (isset($container[$class->name])) {
                    $route->setDefault('_controller', $class->name.':'.$method->name);
                } elseif ('__invoke' === $method->name) {
                    $route->setDefault('_controller', $class->name);
                } else {
                    $route->setDefault('_controller', $class->name.'::'.$method->name);
                }
            });

            $container['router.loader.annotation.class'] = function (Container $container) {
                return new AnnotationClassLoader(
                    is_string($reader = $container['router.annotation.reader']) ? $container[$reader] : $reader,
                    $container['router.annotation.configuration_strategy']
                );
            };

            $container['router.loader.annotation.file'] = function (Container $container) {
                return new AnnotationFileLoader($container['router.file_locator'], $container['router.loader.annotation.class']);
            };

            $container['router.loader.annotation.directory'] = function (Container $container) {
                return new AnnotationDirectoryLoader($container['router.file_locator'], $container['router.loader.annotation.class']);
            };

            $loaders[] = 'router.loader.annotation.class';
            $loaders[] = 'router.loader.annotation.file';
            $loaders[] = 'router.loader.annotation.directory';
        }

        $container['router.loaders'] = $loaders;

        $container['router.delegating_loader'] = function (Container $container) {
            return new DelegatingLoader($container['router.loader_resolver']);
        };

        $container['router.loader_resolver'] = function (Container $container) {
            $loaders = [];
            foreach ($container['router.loaders'] as $serviceName) {
                $loaders[] = $container[$serviceName];
            }

            return new LoaderResolver($loaders);
        };

        $container['router.loader'] = function (Container $container) {
            return new ArrayLoader($container, 'router.delegating_loader');
        };

        $container->extend('url_generator', function ($silexGenerator, $container) {
            return new ChainUrlGenerator([$container['router'], $silexGenerator], $container['request_context']);
        });

        $container->extend('request_matcher', function ($silexMatcher, $container) {
            return new ChainRequestMatcher([$container['router'], $silexMatcher], $container['request_context']);
        });
    }
}
