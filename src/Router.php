<?php
declare(strict_types=1);
namespace Coroq\Router;

use InvalidArgumentException;

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

  public function route(array $waypoints): array {
    try {
      return $this->routeWithMap($this->map, $waypoints);
    }
    catch (RouteNotFoundException) {
      return [];
    }
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
