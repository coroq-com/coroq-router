<?php
declare(strict_types=1);
namespace Coroq\Router;

/**
 * Router that always returns the same result regardless of waypoints
 */
class CatchAllRouter implements RouterInterface {
  private $result;

  public function __construct(
    string $result,
  ) {
    $this->result = $result;
  }

  public function route(array $waypoints): array {
    return [$this->result];
  }
}
