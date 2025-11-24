<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

interface PathRewriteRuleInterface {
  public function apply(string $path): ?PathRewriteResult;
}
