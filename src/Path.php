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
}
