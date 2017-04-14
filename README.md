
[![Build Status](https://travis-ci.org/skalpa/silex-router-provider.svg?branch=master)](https://travis-ci.org/skalpa/silex-router-provider)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/skalpa/silex-router-provider/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/skalpa/silex-router-provider/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/skalpa/silex-router-provider/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/skalpa/silex-router-provider/?branch=master)

# Symfony Router Service Provider for Silex 2.x / Pimple 3.x

Lets you to use the full Symfony Router class in your
Silex/Pimple application.

## Installation

Install the service provider using composer:

```bash
composer require skalpa/silex-router-provider
```

If you want to configure your routes using annotations, you'll also
need the [SensioFrameworkExtraBundle](https://github.com/sensiolabs/SensioFrameworkExtraBundle)
and a Doctrine annotations reader (see below):

```bash
composer require sensio/framework-extra-bundle
```

## Registration

```php
$app->register(new \Skalpa\Silex\Symfony\RouterServiceProvider(), [
    'router.resource' => '/path/to/routing.yml',
    'router.cache_dir' => '/path/to/cache',
]);
```

## Configuration parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `router.debug`                | `bool`                    | Whether to enable the Router class debug mode |
| `router.resource`             | `string`&#124;`string[]`  | Where to load the router configuration from |
| `router.resource_type`        | `string`                  | Type of the resource (optional, needs to be set to `annotation` to import annotations from a PHP file) |
| `router.cache_dir`            | `string`                  | Where the request matcher and URL generator should be dumped |
| `router.file_locator.paths`   | `string[]`                | List of folders passed to the file locator |
| `router.options`              | `array`                   | Additional options passed to the Router |
| `router.loaders`              | `string[]`                | List of resource loaders that should be registered |

## Usage


