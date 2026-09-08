<?php
declare(strict_types=1);
namespace Coroq\Router;

/**
 * Converts between URL paths and segments
 *
 * Segments are the form paths are handled in throughout this library:
 * a path split at "/", with each segment percent-decoded.
 */
class Path {
  /**
   * Convert a URL path into segments
   *
   * @param string $path URL path like "/users/detail"
   * @return array<string> Percent-decoded path segments
   */
  public static function toSegments(string $path): array {
    $segments = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));
    return array_map('rawurldecode', $segments);
  }

  /**
   * Convert segments back into a URL path
   *
   * @param array<string> $segments
   */
  public static function fromSegments(array $segments): string {
    return '/' . implode('/', array_map('rawurlencode', $segments));
  }

  /**
   * Remove the base path of an application from segments
   *
   * Route maps are written relative to the application root, so a request path
   * must have the base path removed before it is routed.
   *
   * @param string $basePath Base path of the application like "/system/survey"
   * @param array<string> $segments Segments of a request path
   * @return array<string> Segments relative to the application root
   * @throws RouteNotFoundException When the segments are not under the base path
   */
  public static function removeBasePath(string $basePath, array $segments): array {
    $baseSegments = self::toSegments($basePath);

    foreach ($baseSegments as $index => $baseSegment) {
      if (($segments[$index] ?? null) !== $baseSegment) {
        throw new RouteNotFoundException(
          sprintf('%s is not under the base path %s', self::fromSegments($segments), $basePath),
          $segments
        );
      }
    }

    return array_slice($segments, count($baseSegments));
  }
}
