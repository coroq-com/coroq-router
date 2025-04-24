<?php
declare(strict_types=1);
namespace Coroq\Router;

/**
 * Provides path-to-waypoints conversion for routers
 */
trait PathRouting {
  /**
   * Find handlers for a given path
   *
   * @param string $path URL path like "/users/profile"
   * @return array List of handlers matching the path
   * @throws RouteNotFoundException When no route matches the path
   */
  public function routePath(string $path): array {
    assert($this instanceof RouterInterface);

    // Convert path to waypoints
    $path = trim($path, '/');
    $waypoints = $path === '' ? [''] : explode('/', $path);
    
    return $this->route($waypoints);
  }
}
