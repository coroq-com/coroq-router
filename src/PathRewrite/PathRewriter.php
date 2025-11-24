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

  public function rewrite(string $path): PathRewriteResult {
    $params = [];
    foreach ($this->rules as $rule) {
      $result = $rule->apply($path);
      if ($result !== null) {
        $path = $result->path;
        $duplicates = array_intersect_key($result->params, $params);
        if ($duplicates) {
          $name = array_keys($duplicates)[0];
          throw new \InvalidArgumentException("Duplicate parameter name across rules: {$name}");
        }
        $params += $result->params;
      }
    }
    return new PathRewriteResult($path, $params);
  }
}
