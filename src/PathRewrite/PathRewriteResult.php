<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

class PathRewriteResult {
  public string $path;
  /** @var array<string, string> */
  public array $params;

  /**
   * @param array<string, string> $params
   */
  public function __construct(string $path, array $params) {
    $this->path = $path;
    $this->params = $params;
  }
}
