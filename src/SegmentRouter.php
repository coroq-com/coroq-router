<?php
declare(strict_types=1);
namespace Coroq\Router;

/**
 * Router that matches one segment against a fixed value
 *
 * MapRouter uses it for every string key in a route map. Use it directly for
 * segments that PHP cannot keep as an array key, such as decimal integers.
 */
class SegmentRouter implements RouterInterface {
  private string $segment;

  /** @var mixed */
  private $value;

  /**
   * @param mixed $value Handler, nested route map, or router for the segment
   */
  public function __construct(int|string $segment, $value) {
    $this->segment = (string)$segment;
    $this->value = is_array($value) ? new MapRouter($value) : $value;
  }

  public function route(array $segments): array {
    if (($segments[0] ?? '') !== $this->segment) {
      throw new RouteSkipException();
    }

    if ($this->value instanceof RouterInterface) {
      try {
        return $this->value->route(array_slice($segments, 1));
      }
      catch (RouteNotFoundException $exception) {
        // Report the path from here down, not just the part below this segment
        throw new RouteNotFoundException(
          sprintf('No route for %s', Path::fromSegments(array_merge([$this->segment], $exception->getSegments()))),
          array_merge([$this->segment], $exception->getSegments())
        );
      }
    }

    // A value that is not a router can not go deeper, so it only matches the last segment
    if (count($segments) <= 1) {
      return [$this->value];
    }
    throw new RouteSkipException();
  }
}
