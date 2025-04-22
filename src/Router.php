<?php
declare(strict_types=1);
namespace Coroq\Router;

use InvalidArgumentException;

/**
 * A minimal router that maps URL paths to handlers using a recursive array structure.
 *
 * Route maps use a simple convention:
 * - Items with numeric keys are always included in results (useful for middleware)
 * - Items with string keys are matched against path segments
 * - Empty string keys ('') match empty path segments
 */
class Router {
  private array $map;

  public function __construct(
    array $map = [],
  ) {
    $this->setMap($map);
  }

  public function setMap(array $map): void {
    $this->map = $map;
  }

  /**
   * Find handlers for a given path
   *
   * @param string $path URL path like "/users/profile"
   * @return array List of handlers matching the path
   * @throws RouteNotFoundException When no route matches the path
   */
  public function route(string $path): array {
    // Convert path to waypoints
    $path = trim($path, '/');
    $waypoints = $path === '' ? [''] : explode('/', $path);
    
    return $this->routeWithMap($this->map, $waypoints);
  }
  
  private function routeWithMap(array $map, array $waypoints): array {
    $route = [];

    $waypoint = array_shift($waypoints) ?? '';
    if (!is_string($waypoint)) {
      throw new InvalidArgumentException();
    }

    foreach ($map as $key => $value) {
      if (is_int($key)) {
        $route[] = $value;
        continue;
      }

      assert(is_string($key));

      if ($key == $waypoint) {
        if (is_array($value)) {
          return array_merge($route, $this->routeWithMap($value, $waypoints));
        }

        $route[] = $value;
        if (!$waypoints) {
          return $route;
        }
      }
    }
    throw new RouteNotFoundException();
  }
}
