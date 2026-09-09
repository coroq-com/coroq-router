# coroq/router

A minimal PHP router for mapping request paths to handlers.

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
use Coroq\Router\Path;
use App\Controller;

$router = new MapRouter([
    '' => Controller\HomeController::class,
    'users' => Controller\User\ListController::class,
]);

$router->route(Path::toSegments('/'));       // [Controller\HomeController::class]
$router->route(Path::toSegments('/users'));  // [Controller\User\ListController::class]
$router->route(Path::toSegments('/nope'));   // throws RouteNotFoundException
```

## A More Complex Example

```php
<?php
use Coroq\Router\MapRouter;
use Coroq\Router\Path;
use Coroq\Router\PathRewrite\PathRewriter;
use App\Controller;
use App\Middleware;

// Request path: /system/survey/users/42

// Split the request path into segments
$segments = Path::toSegments($request->getUri()->getPath());
// ['system', 'survey', 'users', '42']

// Remove the base path the application is deployed under
$segments = Path::removeBasePath('/system/survey', $segments);
// ['users', '42']

// Rewrite the dynamic segment into a fixed name, taking its value out
$rewriter = new PathRewriter();
$rewriter->addRule('/users/{userId:int}');
[$segments, $params] = $rewriter->rewrite($segments);
// $segments = ['users', 'userId'], $params = ['userId' => '42']

// Route the segments to handlers
$router = new MapRouter([
    Middleware\Auth::class,                  // numeric key: collected on the way
    '' => Controller\HomeController::class,  // '' matches the end of the path
    'users' => [                             // nested array: one level deeper
        Middleware\UserMiddleware::class,
        '' => Controller\User\ListController::class,
        'userId' => Controller\User\ShowController::class,
    ],
]);
$handlers = $router->route($segments);
// [Middleware\Auth::class, Middleware\UserMiddleware::class, Controller\User\ShowController::class]
```

## Using the Handlers

What to do with a route is up to the application. A common arrangement is to make the values in the route map class names of PSR-15 middleware, ending with a request handler, and run them as a chain:

```php
$handlers = $router->route($segments);
// [App\Middleware\Auth::class, App\Controller\User\ShowController::class]

$queue = array_map(fn(string $class) => $container->get($class), $handlers);

$request = $request->withAttribute('params', $params);  // path parameters from PathRewriter
$response = $runner->handle($request, $queue);
```

`$container` is any PSR-11 container and `$runner` any PSR-15 runner (Relay, for example). The last class in the route is the one that produces the response, so each route has its own request handler instead of one handler dispatching between actions.

None of this is required, though. Route map values can be closures, objects, or whatever else suits the application - the library collects them and hands them back.

## Route Maps

A route map is a nested array that mirrors the path hierarchy. Routing walks it one segment at a time, collecting values along the way:

```php
$router = new MapRouter([
    Middleware\Auth::class,
    '' => Controller\HomeController::class,
    'users' => [
        Middleware\UserMiddleware::class,
        '' => Controller\User\ListController::class,
        'detail' => Controller\User\DetailController::class,
    ],
]);

$router->route([]);
// [Middleware\Auth::class, Controller\HomeController::class]

$router->route(['users']);
// [Middleware\Auth::class, Middleware\UserMiddleware::class, Controller\User\ListController::class]

$router->route(['users', 'detail']);
// [Middleware\Auth::class, Middleware\UserMiddleware::class, Controller\User\DetailController::class]
```

The keys are read like this:

- **Numeric keys** - Collected as routing passes them (middleware, shared handlers)
- **String keys** - Matched against the current segment; a match descends one level
- **Empty string key (`''`)** - Matches the end of the path
- **Nested arrays** - One level deeper in the path

Entries are processed in order, and matching stops as soon as a key matches, so a numeric-keyed value is collected only when it comes before the key that matches. A value that is not an array matches only when the path ends there; if segments remain below it, it is not a match.

PHP turns a decimal integer array key into an int, so `'2026' => ...` is read as a numeric key and its value is collected on every route instead of matching the segment `2026`. Use `SegmentRouter` for those segments:

```php
$router = new MapRouter([
    'archive' => [
        new SegmentRouter(2026, ['' => Controller\Archive\Year2026::class]),
        new SegmentRouter(2025, Controller\Archive\Year2025::class),
        'latest' => Controller\Archive\LatestController::class,
    ],
]);
```

`SegmentRouter` matches one segment against a fixed value, and reads that value the way a route map does: a nested array goes one level deeper, a router is delegated to, and anything else matches when the path ends there. MapRouter uses it for every string key in a map, so nothing else about routing changes.

When nothing matches, `RouteNotFoundException` is thrown - catch it at the entry point of the application to render your 404 page. It reports the segments it was given, both in its message and through `getSegments()`.

## Segments

Paths are handled as **segments** throughout this library: the path is split at `/` and each segment is percent-decoded.

```php
use Coroq\Router\Path;

Path::toSegments('/users/detail');                 // ['users', 'detail']
Path::toSegments('/');                             // []
Path::toSegments('/john%20doe');                   // ['john doe']
Path::toSegments('/%E6%97%A5%E6%9C%AC%E8%AA%9E');  // ['日本語']
Path::toSegments('/a%2Fb');                        // ['a/b'] - one segment

Path::fromSegments(['users', 'detail']);           // '/users/detail'
```

`RouterInterface::route()` and `PathRewriter::rewrite()` both take segments, so convert the request path once, at the entry point of the application. Because segments are decoded before matching, non-ASCII route map keys work as written in the source code.

## Base Path

When the application is not deployed at the document root, remove its base path before routing:

```php
// Request path: /system/survey/users/detail
$segments = Path::toSegments($request->getUri()->getPath());
$segments = Path::removeBasePath('/system/survey', $segments);  // ['users', 'detail']
```

Requests outside the base path throw `RouteNotFoundException` - the same exception routing throws, so a single catch block handles both.

This library does not generate URLs from routes. When emitting links or redirects, prepend the base path yourself.

## Path Rewriting

MapRouter matches segments exactly as strings. When you need dynamic segments like `/user/123` or `/post/hello-world`, use `PathRewriter` to extract parameters first, then route the normalized segments.

```php
$rewriter = new PathRewriter();
$rewriter->addRules([
    '/post/{postName}',
]);

// Rewrite extracts parameters and normalizes the segments
[$segments, $params] = $rewriter->rewrite(Path::toSegments('/post/hello-world'));
// $segments = ['post', 'postName']
// $params   = ['postName' => 'hello-world']

// Route using the normalized segments
$routeMap = [
    'post' => [
        'postName' => Controller\Post\ShowController::class,
    ],
];

$router = new MapRouter($routeMap);
$handlers = $router->route($segments);
```

### Placeholder Types

Use type constraints to restrict what a placeholder matches:

| Type | Pattern | Example |
|------|---------|---------|
| `any` (default) | `.+` | `hello-world`, `日本語` |
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
// ['user', '42', 'post', '99'] → segments: ['user', 'userid', 'post', 'postid']
//                                params: ['userid' => '42', 'postid' => '99']
```

```php
$rewriter = new PathRewriter();
$rewriter->addRule('/file/{name}.{ext}');
// ['file', 'report.pdf'] → segments: ['file', 'name.ext']
//                          params: ['name' => 'report', 'ext' => 'pdf']
```

```php
$rewriter = new PathRewriter();
$rewriter->addRule('/archive/{year:int}-{month:int}-{day:int}');
// ['archive', '2025-01-15'] → segments: ['archive', 'year-month-day']
//                             params: ['year' => '2025', 'month' => '01', 'day' => '15']
```

Placeholder names must be ASCII, because they become named capture groups. The literal parts of a pattern and the matched values can be anything.

### Rule Matching Order

Rules are applied sequentially. Segments that no rule matches pass through unchanged, so a rule that never fires is not an error - the segments simply arrive at the router as they were. Each rule matches from the first segment; remaining segments are preserved:

```php
$rewriter = new PathRewriter();
$rewriter->addRule('/user/{userid:int}');

// ['user', '123', 'posts'] → segments: ['user', 'userid', 'posts'], params: ['userid' => '123']
// The 'posts' segment is preserved for further routing
```

Multiple rules can work together, with params accumulating:

```php
$rewriter = new PathRewriter();
$rewriter->addRules([
    '/user/{userid:int}',
    '/user/userid/{action:alpha}',
]);

// ['user', '6755', 'edit']
// → first rule: segments become ['user', 'userid', 'edit'], params: ['userid' => '6755']
// → second rule: segments become ['user', 'userid', 'action'], params: ['userid' => '6755', 'action' => 'edit']
```

### Custom Rules

For advanced matching, implement `PathRewriteRuleInterface`:

```php
use Coroq\Router\PathRewrite\PathRewriteRuleInterface;
use Coroq\Router\PathRewrite\PathRewriteResult;

class ArchiveRule implements PathRewriteRuleInterface {
    public function apply(array $segments): ?PathRewriteResult {
        if (($segments[0] ?? null) !== 'archive' || count($segments) < 2) {
            return null;
        }
        return new PathRewriteResult(
            array_merge(['archive', 'year'], array_slice($segments, 2)),
            ['year' => $segments[1]],
        );
    }
}

$rewriter->addRule(new ArchiveRule());
```

Rules that match across segment boundaries are easier to write against a path string. Extend `PathStringRule` for those - it converts the segments to a path string, and the returned path back into segments:

```php
use Coroq\Router\PathRewrite\PathStringRule;

class RegexRule extends PathStringRule {
    protected function applyToPath(string $path): ?array {
        if (preg_match('#^/legacy/(.+)/page(\d+)$#', $path, $m)) {
            return ['/legacy/item/page', [
                'item' => $m[1],
                'page' => $m[2],
            ]];
        }
        return null;
    }
}

$rewriter->addRule(new RegexRule());
```

A path string cannot represent a segment containing a slash, so `PathStringRule` does not apply to segments that contain one.

## Custom Routers

Any `RouterInterface` can be placed in a route map, and MapRouter delegates to it. Under a string key it receives the segments below that key:

```php
use Coroq\Router\RouterInterface;
use App\Controller;

class DocsRouter implements RouterInterface {
    public function route(array $segments): array {
        // ['a', 'b'] for /docs/a/b
        return [Controller\DocumentController::class];
    }
}

$routeMap = [
    'docs' => new DocsRouter(),
];
```

Under a numeric key it receives the segments unchanged, and is reached when none of the entries before it matched - so a router meant as a fallback belongs at the end of the map. Note that it only covers its own level: once a string key matches, routing descends into that level and never comes back to the outer map.

A router cannot always tell in advance whether it applies - it may have to look something up first. Throwing `RouteSkipException` from `route()` says "not this one": MapRouter ignores that entry and carries on through the rest of the map. A `RouteNotFoundException` from a delegated router, by contrast, ends routing.

## License

MIT