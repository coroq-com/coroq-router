<?php
declare(strict_types=1);
namespace Coroq\Router;

/**
 * Defines router segments that process segments and return matched handlers
 */
interface RouterInterface {
  /**
   * Process segments and return matched handlers
   *
   * @param list<string> $segments Segments of a request path, as Path::toSegments() returns them
   * @return list<mixed> Handlers matched for the segments
   * @throws RouteSkipException When this router does not match the segments
   * @throws RouteNotFoundException When this router matches but has no route for the segments
   */
  public function route(array $segments): array;
}
