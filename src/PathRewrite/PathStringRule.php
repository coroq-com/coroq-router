<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

/**
 * Base class for rules that are easier to write against a path string
 *
 * Useful for rules matching across segment boundaries, such as a single
 * regular expression over the whole path.
 */
abstract class PathStringRule implements PathRewriteRuleInterface {
  final public function apply(array $segments): ?PathRewriteResult {
    foreach ($segments as $segment) {
      // A path string cannot represent a segment containing a slash
      if (str_contains($segment, '/')) {
        return null;
      }
    }
    return $this->applyToPath('/' . implode('/', $segments));
  }

  /**
   * @return PathRewriteResult|null Null when the rule does not apply
   */
  abstract protected function applyToPath(string $path): ?PathRewriteResult;
}
