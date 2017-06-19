<?php

use Skalpa\Silex\Symfony\Routing\Tests\Fixtures\FooController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$collection = new RouteCollection();
$collection->add(
    'test',
    new Route('/foo', ['_controller' => FooController::class.'::fooAction'])
);

return $collection;
