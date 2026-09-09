<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

interface PathRewriteRuleInterface {
  /**
   * Rewrite segments and extract parameters
   *
   * @param array<string> $segments
   * @return PathRewriteResult|null Null when the rule does not apply
   */
  public function apply(array $segments): ?PathRewriteResult;
}
