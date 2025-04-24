# coroq/router

A minimal PHP router for mapping request paths to class names.

## Installation

```bash
composer require coroq/router
```

## What it does

Maps URL paths to handlers using nested arrays with a simple convention:
- Numeric keys are always included in results (useful for middleware)
- String keys are matched against path segments

```php
<?php
use Coroq\Router\MapRouter;
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
$router = new MapRouter($routeMap);

// Get handlers for a path
$handlers = $router->routePath('/');  // Returns [Auth::class, App\Controller\HomeController::class]
$handlers = $router->routePath('/users');  // Returns [Auth::class, ListController::class]
$handlers = $router->routePath('/users/detail');  // Returns [Auth::class, ListController::class, DetailController::class]

// Non-existent routes throw RouteNotFoundException
try {
    $handlers = $router->routePath('/nope');
} catch (Coroq\Router\RouteNotFoundException $e) {
    // Handle route not found
}

// Leading and trailing slashes are handled automatically
$handlers = $router->routePath('/users/detail/');  // Same as '/users/detail'

// Using CatchAllRouter for fallback handlers
use Coroq\Router\CatchAllRouter;

$catchAll = new CatchAllRouter('App\Controller\NotFoundController');
$routeMap = [
    'users' => [
        // Normal route handling
        'profile' => 'App\Controller\ProfileController',
    ],
    // This will catch any other routes that don't match
    $catchAll,
];

$router = new MapRouter($routeMap);
$handlers = $router->routePath('/unknown/path');  // Returns ['App\Controller\NotFoundController']
```

## Why use this?

This router is for you if:

- You need a simple way to map URL paths to class names
- You want a visual representation of your route hierarchy
- You prefer array-based configuration over annotations/attributes
- You're building a small application or API
- You value code simplicity and composability

## Notes

- PHP 8.0+ required
- Empty string keys (`''`) match empty path segments
- Non-existent routes throw RouteNotFoundException
- CatchAllRouter provides a simple way to handle fallback routes
- No regex, no named parameters, just simple path segment matching
- Can be used with PSR-15 middleware by processing the returned handlers

## License

MIT