# Upgrading from 1.x to 2.0

In 1.x a path travelled through the library as a string, and each part of the
library split it again on its own. In 2.0 the path is split and percent-decoded
once, at the entry point of the application, and everything after that works on
**segments**: an array of decoded path components.

## The front controller

```php
// 1.x
$result = $rewriter->rewrite($request->getUri()->getPath());
$handlers = $router->routePath($result->path);
$params = $result->params;

// 2.0
$segments = Path::toSegments($request->getUri()->getPath());
[$segments, $params] = $rewriter->rewrite($segments);
$handlers = $router->route($segments);
```

## Routing

**`routePath()` and the `PathRouting` trait are gone.** Convert the path
yourself and call `route()`:

```php
// 1.x
$handlers = $router->routePath('/users/detail');

// 2.0
$handlers = $router->route(Path::toSegments('/users/detail'));
```

**Segments are percent-decoded.** A route map key is compared against the
decoded segment, so `'日本語' => ...` now matches `/%E6%97%A5%E6%9C%AC%E8%AA%9E`,
and a segment may contain a slash (`/a%2Fb` is the single segment `a/b`).

**The root path is an empty array.** `route([])` and `route([''])` both reach
the `''` key, but `Path::toSegments('/')` returns `[]`.

**Segments are compared strictly.** In 1.x the comparison was `==`, so a key
like `'1e2'` matched the segment `'100'`. It no longer does.

**`MapRouter::setMap()` is gone and the constructor argument is required.**

```php
// 1.x
$router = new MapRouter();
$router->setMap($routeMap);

// 2.0
$router = new MapRouter($routeMap);
```

**`CatchAllRouter` is gone.** Catch `RouteNotFoundException` at the entry point
for a 404 page, or write the router yourself:

```php
class NotFoundRouter implements RouterInterface {
    public function route(array $segments): array {
        return [Controller\NotFoundController::class];
    }
}
```

**`RouteNotFoundException` carries context.** It has a message naming the path
and a `getSegments()` method. Its constructor is
`__construct(string $message = '', array $segments = [])`.

**A matched value no longer leaks when the path continues below it.** In 1.x,
`['users' => Controller\User\ListController::class, new SomeRouter()]` routed
`/users/detail` to `[ListController::class, ...SomeRouter's result]`. The
controller is no longer included. This is also fixed in 1.0.1.

## Path rewriting

**`rewrite()` takes segments and returns a list.**

```php
// 1.x
$result = $rewriter->rewrite('/user/123');
$result->path;    // '/user/id'
$result->params;  // ['id' => '123']

// 2.0
[$segments, $params] = $rewriter->rewrite(['user', '123']);
// $segments = ['user', 'id'], $params = ['id' => '123']
```

**`PathRewriteResult` holds segments.** `->path` is now `->segments`, and
`PathRewriteResult::fromPath()` has been removed.

**Custom rules take segments.**

```php
// 1.x
public function apply(string $path): ?PathRewriteResult

// 2.0
public function apply(array $segments): ?PathRewriteResult
```

For a rule that is easier to write against the whole path, extend the new
`PathStringRule` instead. It converts the segments to a path string for you,
and the path you return back into segments:

```php
class RegexRule extends PathStringRule {
    protected function applyToPath(string $path): ?array {
        if (preg_match('#^/legacy/(.+)/page(\d+)$#', $path, $m)) {
            return ['/legacy/item/page', ['item' => $m[1], 'page' => $m[2]]];
        }
        return null;
    }
}
```

**The `any` placeholder type matches a whole segment.** Its pattern changed
from `[^/]+` to `.+`, so a segment that contains a decoded slash now matches.

**A placeholder pattern is compiled when the rule is constructed.** An unknown
type or a duplicate parameter name used to throw on the first request that
reached the rule; it now throws from `new PlaceholderRule(...)` and from
`new PathRewriter(...)`.

**`PathRewriter` takes its rules as a constructor argument.** `addRule()` and
`addRules()` are gone, so a rewriter cannot change after it is built.

```php
// 1.x
$rewriter = new PathRewriter();
$rewriter->addRules(['/user/{id:int}']);

// 2.0
$rewriter = new PathRewriter(['/user/{id:int}']);
```

## New in 2.0

- `Path::toSegments()`, `Path::fromSegments()` and `Path::removeBasePath()`,
  for applications deployed under a base path.
- `SegmentRouter`, which matches one segment against a fixed value. Use it for
  segments PHP cannot keep as a string array key, such as `2026`.
- `PathStringRule`, for rewrite rules written against a path string.
