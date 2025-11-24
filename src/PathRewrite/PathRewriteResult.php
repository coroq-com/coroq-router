<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

class PathRewriteResult {
  /**
   * @param array<string, string> $params
   */
  public function __construct(
    public readonly string $path,
    public readonly array $params,
  ) {}
}
