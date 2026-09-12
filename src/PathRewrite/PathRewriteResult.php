<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

class PathRewriteResult {
  /** @var list<string> */
  public array $segments;
  /** @var array<string, string> */
  public array $params;

  /**
   * @param list<string> $segments
   * @param array<string, string> $params
   */
  public function __construct(array $segments, array $params) {
    $this->segments = $segments;
    $this->params = $params;
  }
}
