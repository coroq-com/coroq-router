<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

class PathRewriter {
  /** @var list<PathRewriteRuleInterface> */
  private array $rules;

  /**
   * @param array<PathRewriteRuleInterface|string> $rules
   */
  public function __construct(array $rules) {
    $this->rules = array_map(
      fn($rule) => is_string($rule) ? new PlaceholderRule($rule) : $rule,
      array_values($rules)
    );
  }

  /**
   * Apply the rules to segments
   *
   * @param list<string> $segments
   * @return array{0: list<string>, 1: array<string, string>} Rewritten segments and parameters
   */
  public function rewrite(array $segments): array {
    $params = [];
    foreach ($this->rules as $rule) {
      $result = $rule->apply($segments);
      if ($result !== null) {
        $segments = $result->segments;
        $duplicates = array_intersect_key($result->params, $params);
        if ($duplicates) {
          $name = array_keys($duplicates)[0];
          throw new \InvalidArgumentException("Duplicate parameter name across rules: {$name}");
        }
        $params += $result->params;
      }
    }
    return [$segments, $params];
  }
}
