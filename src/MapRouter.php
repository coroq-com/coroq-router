<?php
declare(strict_types=1);
namespace Coroq\Router;

use InvalidArgumentException;

/**
 * Array-based router that maps segments to handlers using a recursive structure
 *
 * Route maps use a simple convention:
 * - Items with numeric keys are always included in results (useful for middleware)
 * - Items with string keys are matched against segments
 * - Empty string keys ('') match empty segments
 * - RouterInterface instances are delegated to for further processing
 */
class MapRouter implements RouterInterface {
  private array $map;

  public function __construct(
    array $map = [],
  ) {
    $this->setMap($map);
  }

  public function setMap(array $map): void {
    $this->map = $map;
  }

  public function route(array $segments): array {
    return $this->routeWithMap($this->map, $segments, $segments);
  }

  /**
   * @param array<string> $routedSegments Segments given to route(), used for error messages
   */
  private function routeWithMap(array $map, array $segments, array $routedSegments): array {
    $route = [];
    $segment = $segments[0] ?? '';

    if (!is_string($segment)) {
      throw new InvalidArgumentException(sprintf(
        'Segments must be strings, %s given.',
        get_debug_type($segment)
      ));
    }

    foreach ($map as $key => $value) {
      try {
        if (is_int($key)) {
          if ($value instanceof RouterInterface) {
            return array_merge($route, $value->route($segments));
          }
          $route[] = $value;
          continue;
        }

        assert(is_string($key));

        if ($key == $segment) {
          if ($value instanceof RouterInterface) {
            return array_merge($route, $value->route(array_slice($segments, 1)));
          }

          if (is_array($value)) {
            return array_merge($route, $this->routeWithMap($value, array_slice($segments, 1), $routedSegments));
          }

          // A scalar value can not go deeper, so it only matches the last segment
          if (count($segments) <= 1) {
            $route[] = $value;
            return $route;
          }
        }
      }
      catch (RouteSkipException) {
        continue;
      }
    }
    throw new RouteNotFoundException(
      sprintf('No route for %s', Path::fromSegments($routedSegments)),
      $routedSegments
    );
  }
}
