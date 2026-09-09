<?php
declare(strict_types=1);
namespace Coroq\Router;

/**
 * Defines router segments that process segments and return matched handlers
 */
interface RouterInterface {
  /**
   * Process segments and return matched handlers
   */
  public function route(array $segments): array;
}
