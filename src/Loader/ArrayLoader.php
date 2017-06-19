<?php

namespace Skalpa\Silex\Symfony\Routing\Loader;

use Pimple\Container;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Lazy loader that can load multiple resources.
 *
 * Array of resources can be:
 * - Associative arrays with resource path as key and resource type as value
 * - Numerical arrays with resource path as value
 */
class ArrayLoader implements LoaderInterface
{
    private $container;
    private $serviceName;
    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * ArrayLoader constructor.
     *
     * @param Container $container
     * @param string    $serviceName
     */
    public function __construct(Container $container, $serviceName)
    {
        $this->container = $container;
        $this->serviceName = $serviceName;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        if (!is_array($resource)) {
            return $this->getLoader()->load($resource, $type);
        }

        $loader = $this->getLoader();
        $collection = new RouteCollection();

        foreach ($resource as $key => $value) {
            $collection->addCollection(is_int($key) ? $loader->load($value) : $loader->load($key, $value));
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        if (!is_array($resource)) {
            return $this->getLoader()->supports($resource, $type);
        }

        $loader = $this->getLoader();
        $supported = true;

        foreach ($resource as $key => $value) {
            $supported = $supported && (is_int($key) ? $loader->supports($value) : $loader->supports($key, $value));
        }

        return $supported;
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
        return $this->getLoader()->getResolver();
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        return $this->getLoader()->setResolver($resolver);
    }

    protected function getLoader()
    {
        if (null === $this->loader) {
            $this->loader = $this->container[$this->serviceName];
        }

        return $this->loader;
    }
}
