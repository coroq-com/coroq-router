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
use App\Middleware;
use App\Controller;

// Define a route map
$routeMap = [
    // Numeric keys always get included in results
    Middleware\Auth::class,

    // Empty string key matches root path
    '' => Controller\HomeController::class,

    // String keys map to path segments
    'users' => [
        // Nested numeric keys also get included
        Middleware\UserMiddleware::class,

        // Empty string key matches /users
        '' => Controller\User\ListController::class,

        // Nested paths
        'detail' => Controller\User\DetailController::class,
    ],
];

// Create router
$router = new MapRouter($routeMap);

// Get handlers for a path
$handlers = $router->routePath('/');
// Returns [Middleware\Auth::class, Controller\HomeController::class]

$handlers = $router->routePath('/users');
// Returns [Middleware\Auth::class, Middleware\UserMiddleware::class, Controller\User\ListController::class]

$handlers = $router->routePath('/users/detail');
// Returns [Middleware\Auth::class, Middleware\UserMiddleware::class, Controller\User\DetailController::class]

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

## Path Rewriting

You can use PathRewriter to extract parameters from URLs and convert them to normalized paths before routing.

```php
<?php
use Coroq\Router\MapRouter;
use Coroq\Router\PathRewrite\PathRewriter;
use App\Controller;

// Set up rewriter with placeholder rules
$rewriter = new PathRewriter();
$rewriter->addRules([
    '/post/{postName}',
]);

// Rewrite extracts parameters and normalizes the path
$result = $rewriter->rewrite('/post/hello-world');
// $result->path   = '/post/postName'
// $result->params = ['postName' => 'hello-world']

// Route using the normalized path
$routeMap = [
    'post' => [
        'postName' => Controller\Post\ShowController::class,
    ],
];

$router = new MapRouter($routeMap);
$handlers = $router->routePath($result->path);
// Caller has both $handlers and $result->params
```

### Placeholder Types

Use type constraints to restrict what a placeholder matches:

| Type | Pattern | Example |
|------|---------|---------|
| (none) | `[^/]+` | any character except slash |
| `any` | `[^/]+` | same as (none), explicit |
| `int` | `[0-9]+` | `123` |
| `alpha` | `[a-zA-Z]+` | `abc` |
| `alnum` | `[a-zA-Z0-9]+` | `abc123` |
| `hex` | `[0-9a-fA-F]+` | `5f3a` |
| `uuid` | UUID format | `550e8400-e29b-41d4-a716-446655440000` |

```php
$rewriter->addRules([
    '/token/{value:hex}',           // matches /token/5f3a, not /token/xyz
    '/item/{id:uuid}',              // matches valid UUIDs only
    '/page/{name}',                 // matches any single segment (default type)
]);
```

### Multiple Placeholders

Placeholders can appear multiple times in a path, or even within a single segment:

```php
$rewriter->addRule('/user/{userid:int}/post/{postid:int}');
// /user/42/post/99 → path: /user/userid/post/postid
//                    params: ['userid' => '42', 'postid' => '99']

$rewriter->addRule('/file/{name}.{ext}');
// /file/report.pdf → path: /file/name.ext
//                     params: ['name' => 'report', 'ext' => 'pdf']

$rewriter->addRule('/archive/{year:int}-{month:int}-{day:int}');
// /archive/2025-01-15 → path: /archive/year-month-day
//                        params: ['year' => '2025', 'month' => '01', 'day' => '15']
```

### Rule Matching Order

Rules are applied sequentially. Each rule matches path components from the beginning; remaining components are preserved as suffix. Params accumulate across all matching rules:

```php
$rewriter->addRules([
    '/user/{userid:int}',
    '/user/userid/{action:alpha}',
]);

// /user/6755/edit
// → first rule matches '/user/6755', rewrites to '/user/userid', suffix '/edit' preserved
//   path: /user/userid/edit, params: [userid => 6755]
// → second rule matches '/user/userid/edit'
//   path: /user/userid/action, params: [userid => 6755, action => edit]
```

### Custom Rules

For advanced matching, implement `PathRewriteRuleInterface`:

```php
use Coroq\Router\PathRewrite\PathRewriteRuleInterface;
use Coroq\Router\PathRewrite\PathRewriteResult;

class RegexRule implements PathRewriteRuleInterface {
    public function apply(string $path): ?PathRewriteResult {
        if (preg_match('#^/legacy/(.+)/page(\d+)$#', $path, $m)) {
            return new PathRewriteResult('/legacy/item/page', [
                'item' => $m[1],
                'page' => $m[2],
            ]);
        }
        return null;
    }
}

$rewriter->addRule(new RegexRule());
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
- PathRewriter provides parameter extraction with type constraints
- Can be used with PSR-15 middleware by processing the returned handlers

## License

MIT