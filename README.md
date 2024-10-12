# coroq/router

A simple PHP router for mapping request paths to class names.

## Installation

```bash
composer require coroq/router
```

## What it does

This is a minimal router that takes an array of "waypoints" (path segments) and returns a list of class names based on a predefined map.

```php
<?php
use Coroq\Router\Router;
use App\Middleware\Auth;
use App\Controller\User\ListController;
use App\Controller\User\DetailController;

// Define a route map
$routeMap = [
    // Numeric keys always get included in results
    Auth::class,
    
    // Empty string key matches root path
    '' => App\Controller\HomeController::class,
    
    // String keys map to path segments
    'users' => [
        // Nested numeric keys also get included
        ListController::class,
        
        // Nested paths
        'detail' => DetailController::class,
    ],
];

// Create router
$router = new Router($routeMap);

// Get handlers for a path
$handlers = $router->route(['']);  // Returns [Auth::class, App\Controller\HomeController::class]
$handlers = $router->route(['users']);  // Returns [Auth::class, ListController::class]
$handlers = $router->route(['users', 'detail']);  // Returns [Auth::class, ListController::class, DetailController::class]
$handlers = $router->route(['nope']);  // Returns [] (empty array)
```

## Why use this?

You probably shouldn't unless:

- You need a really simple way to map URL paths to classes
- You don't want the overhead of a full router with regex, named parameters, etc.
- You're building a small app or middleware system

This router is meant to be used as part of a PSR-15 middleware system, but you can use it for whatever.

## Notes

- PHP 8.0+ required
- Empty string keys (`''`) match empty path segments (e.g., root path)
- Non-existent routes return an empty array

## License

MIT