<?php
declare(strict_types=1);
namespace Coroq\Router;

/**
 * Router that always returns the same result regardless of waypoints
 */
class CatchAllRouter implements RouterInterface {
  public function __construct(
    private string $result,
  ) {
  }

  public function route(array $waypoints): array {
    return [$this->result];
  }
}
