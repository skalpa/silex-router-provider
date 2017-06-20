<?php

namespace Skalpa\Silex\Symfony\Routing\Loader;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Routing\Loader\AnnotationClassLoader as BaseAnnotationClassLoader;
use Symfony\Component\Routing\Route;

/**
 * Annotation loader that uses an external callable to configure routes.
 */
class AnnotationClassLoader extends BaseAnnotationClassLoader
{
    /** @var callable */
    private $configurationStrategy;

    public function __construct(Reader $reader, callable $configurationStrategy)
    {
        parent::__construct($reader);

        $this->configurationStrategy = $configurationStrategy;
    }

    protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annotation)
    {
        $configure = $this->configurationStrategy;
        $configure($route, $class, $method, $annotation);
    }
}
