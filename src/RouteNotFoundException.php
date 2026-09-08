<?php
declare(strict_types=1);
namespace Coroq\Router;

use RuntimeException;

class RouteNotFoundException extends RuntimeException {
  /** @var array<string> */
  private array $segments;

  /**
   * @param array<string> $segments Segments that could not be routed
   */
  public function __construct(string $message = '', array $segments = []) {
    parent::__construct($message);
    $this->segments = $segments;
  }

  /**
   * @return array<string>
   */
  public function getSegments(): array {
    return $this->segments;
  }
}
