<?php
declare(strict_types=1);
namespace Coroq\Router;

use RuntimeException;

class RouteNotFoundException extends RuntimeException {
  /** @var list<string> */
  private array $segments;

  /**
   * @param list<string> $segments Segments that could not be routed
   */
  public function __construct(string $message = '', array $segments = []) {
    parent::__construct($message);
    $this->segments = $segments;
  }

  /**
   * @return list<string>
   */
  public function getSegments(): array {
    return $this->segments;
  }
}
