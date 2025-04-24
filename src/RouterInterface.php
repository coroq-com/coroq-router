<?php
declare(strict_types=1);
namespace Coroq\Router;

/**
 * Defines router components that process waypoints and return matched handlers
 */
interface RouterInterface {
  /**
   * Process waypoints and return matched handlers
   */
  public function route(array $waypoints): array;
}
