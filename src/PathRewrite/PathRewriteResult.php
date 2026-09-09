<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

class PathRewriteResult {
  /** @var array<string> */
  public array $segments;
  /** @var array<string, string> */
  public array $params;

  /**
   * @param array<string> $segments
   * @param array<string, string> $params
   */
  public function __construct(array $segments, array $params) {
    $this->segments = $segments;
    $this->params = $params;
  }

  /**
   * Create a result from a path string
   *
   * The path is only split into segments. Unlike Path::toSegments(), it is
   * not percent-decoded, because it is built from already decoded segments.
   *
   * @param array<string, string> $params
   */
  public static function fromPath(string $path, array $params): self {
    $segments = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));
    return new self($segments, $params);
  }
}
