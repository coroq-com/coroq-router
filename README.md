# coroq/router

A minimal PHP router for mapping request paths to class names.

## Requirements

- PHP 8.0+

## Installation

```bash
composer require coroq/router
```

## Quick Start

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
```

## Concepts

The route map is a nested array structure:

- **Numeric keys** - Always included in results (middleware, shared handlers)
- **String keys** - Matched against path segments
- **Empty string key (`''`)** - Matches the current path level (e.g., `/` at root, `/users` inside `'users'` array)
- **Nested arrays** - Represent path hierarchy

The router returns an array of all matched handlers in order, from root to leaf.

## Fallback Routes

Use `CatchAllRouter` to handle any routes that don't match. Place it as a numeric-keyed item - it will be tried when no string keys match:

```php
use Coroq\Router\CatchAllRouter;

$routeMap = [
    Middleware\Auth::class,
    '' => Controller\HomeController::class,
    'users' => [
        '' => Controller\User\ListController::class,
    ],
    // Catches any unmatched routes
    new CatchAllRouter(Controller\NotFoundController::class),
];

$router = new MapRouter($routeMap);
$handlers = $router->routePath('/unknown/path');
// Returns [Middleware\Auth::class, Controller\NotFoundController::class]
```

## Path Rewriting

MapRouter matches path segments exactly as strings. When you need dynamic segments like `/user/123` or `/post/hello-world`, use `PathRewriter` to extract parameters first, then route the normalized path.

```php
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

// Pass params to your controller however you like
// e.g., $controller->handle($request->withAttribute('params', $result->params));
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
$rewriter = new PathRewriter();
$rewriter->addRules([
    '/token/{value:hex}',           // matches /token/5f3a, not /token/xyz
    '/item/{id:uuid}',              // matches valid UUIDs only
    '/page/{name}',                 // matches any single segment (default type)
]);
```

### Multiple Placeholders

Placeholders can appear multiple times in a path, or even within a single segment:

```php
$rewriter = new PathRewriter();
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

Rules are applied sequentially. Each rule matches from the beginning of the path; remaining segments are preserved:

```php
$rewriter = new PathRewriter();
$rewriter->addRule('/user/{userid:int}');

// /user/123/posts → path: /user/userid/posts, params: [userid => 123]
// The '/posts' suffix is preserved for further routing
```

Multiple rules can work together, with params accumulating:

```php
$rewriter = new PathRewriter();
$rewriter->addRules([
    '/user/{userid:int}',
    '/user/userid/{action:alpha}',
]);

// /user/6755/edit
// → first rule: path becomes /user/userid/edit, params: [userid => 6755]
// → second rule: path becomes /user/userid/action, params: [userid => 6755, action => edit]
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

## PSR-15 Middleware Integration

The returned handler array can be used with PSR-15 middleware pipelines:

```php
$result = $rewriter->rewrite($request->getUri()->getPath());
$handlers = $router->routePath($result->path);
// $handlers = [App\Middleware\Auth::class, App\Controller\User\ShowController::class]

// Build a middleware pipeline from $handlers
// Middleware passes to next; leaf controller returns response
```

Each leaf controller handles one route - no internal action dispatching.

## License

MIT