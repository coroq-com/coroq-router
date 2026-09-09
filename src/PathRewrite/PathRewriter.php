<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

class PathRewriter {
  /** @var array<PathRewriteRuleInterface> */
  private array $rules = [];

  public function __construct() {}

  public function addRule(PathRewriteRuleInterface|string $rule): self {
    if (is_string($rule)) {
      $rule = new PlaceholderRule($rule);
    }
    $this->rules[] = $rule;
    return $this;
  }

  /**
   * @param array<PathRewriteRuleInterface|string> $rules
   */
  public function addRules(array $rules): self {
    foreach ($rules as $rule) {
      $this->addRule($rule);
    }
    return $this;
  }

  /**
   * @param array<string> $segments
   */
  public function rewrite(array $segments): PathRewriteResult {
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
    return new PathRewriteResult($segments, $params);
  }
}
