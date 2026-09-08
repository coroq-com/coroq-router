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

    $result = $this->applyToPath('/' . implode('/', $segments));
    if ($result === null) {
      return null;
    }

    [$path, $params] = $result;
    return new PathRewriteResult(
      array_values(array_filter(explode('/', $path), fn($s) => $s !== '')),
      $params
    );
  }

  /**
   * Rewrite a path string and extract parameters
   *
   * The path is already percent-decoded, and the returned path is not decoded
   * again. Return null when the rule does not apply.
   *
   * @return array{0: string, 1: array<string, string>}|null Rewritten path and parameters
   */
  abstract protected function applyToPath(string $path): ?array;
}
