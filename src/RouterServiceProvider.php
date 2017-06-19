<?php

namespace Skalpa\Silex\Symfony\Routing;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Routing\AnnotatedRouteControllerLoader;
use Skalpa\Silex\Symfony\Routing\Loader\ArrayLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Loader\AnnotationFileLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
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
        $container['router.resource'] = null;
        $container['router.resource_type'] = null;
        $container['router.cache_dir'] = null;
        $container['router.file_locator.paths'] = [];
        $container['router.options'] = [];
        $container['router'] = function (Container $container) {
            $options = array_replace([
                'debug'         => $container['router.debug'],
                'cache_dir'     => $container['router.cache_dir'],
                'resource_type' => $container['router.resource_type'],
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

        if (class_exists(AnnotatedRouteControllerLoader::class) && class_exists(AnnotationReader::class)) {
            $container['router.annotations_reader'] = function () {
                AnnotationRegistry::registerLoader('class_exists');

                return new CachedReader(new AnnotationReader(), new ArrayCache());
            };

            $container['router.annotations_loader'] = function (Container $container) {
                if (is_string($reader = $container['router.annotations_reader'])) {
                    $reader = $container[$reader];
                }

                return new AnnotatedRouteControllerLoader($reader);
            };

            $container['router.loader.annotation_file'] = function (Container $container) {
                return new AnnotationFileLoader($container['router.file_locator'], $container['router.annotations_loader']);
            };

            $container['router.loader.annotation_directory'] = function (Container $container) {
                return new AnnotationDirectoryLoader($container['router.file_locator'], $container['router.annotations_loader']);
            };

            $loaders[] = 'router.loader.annotation_file';
            $loaders[] = 'router.loader.annotation_directory';
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
            return new ChainRequestMatcher([$container['router'], $silexMatcher]);
        });
    }
}
