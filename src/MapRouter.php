<?php
declare(strict_types=1);
namespace Coroq\Router;

use InvalidArgumentException;

/**
 * Array-based router that maps waypoints to handlers using a recursive structure
 *
 * Route maps use a simple convention:
 * - Items with numeric keys are always included in results (useful for middleware)
 * - Items with string keys are matched against waypoints
 * - Empty string keys ('') match empty waypoints
 * - RouterInterface instances are delegated to for further processing
 */
class MapRouter implements RouterInterface {
  use PathRouting;

  private array $map;

  public function __construct(
    array $map = [],
  ) {
    $this->setMap($map);
  }

  public function setMap(array $map): void {
    $this->map = $map;
  }

  public function route(array $waypoints): array {
    return $this->routeWithMap($this->map, $waypoints);
  }

  private function routeWithMap(array $map, array $waypoints): array {
    $route = [];
    $waypoint = $waypoints[0] ?? '';

    if (!is_string($waypoint)) {
      throw new InvalidArgumentException();
    }

    foreach ($map as $key => $value) {
      try {
        if (is_int($key)) {
          if ($value instanceof RouterInterface) {
            return array_merge($route, $value->route($waypoints));
          }
          $route[] = $value;
          continue;
        }

        assert(is_string($key));

        if ($key == $waypoint) {
          if ($value instanceof RouterInterface) {
            return array_merge($route, $value->route(array_slice($waypoints, 1)));
          }

          if (is_array($value)) {
            return array_merge($route, $this->routeWithMap($value, array_slice($waypoints, 1)));
          }

          $route[] = $value;
          if (count($waypoints) <= 1) {
            return $route;
          }
        }
      }
      catch (RouteSkipException) {
        continue;
      }
    }
    throw new RouteNotFoundException();
  }
}
