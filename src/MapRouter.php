<?php
declare(strict_types=1);
namespace Coroq\Router;

use InvalidArgumentException;

/**
 * Array-based router that maps segments to handlers using a recursive structure
 *
 * Route maps use a simple convention:
 * - Items with numeric keys are collected as routing passes them (useful for middleware)
 * - Items with string keys are matched against segments
 * - Empty string keys ('') match empty segments
 * - RouterInterface instances are delegated to for further processing
 */
class MapRouter implements RouterInterface {
  /** @var list<mixed> */
  private array $entries;

  public function __construct(array $map) {
    $this->entries = [];
    foreach ($map as $key => $value) {
      $this->entries[] = is_int($key) ? $value : new SegmentRouter($key, $value);
    }
  }

  public function route(array $segments): array {
    $segment = $segments[0] ?? '';
    if (!is_string($segment)) {
      throw new InvalidArgumentException(sprintf(
        'Segments must be strings, %s given.',
        get_debug_type($segment)
      ));
    }

    $route = [];
    foreach ($this->entries as $entry) {
      if (!($entry instanceof RouterInterface)) {
        $route[] = $entry;
        continue;
      }
      try {
        return array_merge($route, $entry->route($segments));
      }
      catch (RouteSkipException) {
        continue;
      }
    }
    throw new RouteNotFoundException(
      sprintf('No route for %s', Path::fromSegments($segments)),
      $segments
    );
  }
}
