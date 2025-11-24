<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

class PlaceholderRule implements PathRewriteRuleInterface {
  private const TYPE_PATTERNS = [
    'any' => '[^/]+',
    'int' => '[0-9]+',
    'alpha' => '[a-zA-Z]+',
    'alnum' => '[a-zA-Z0-9]+',
    'hex' => '[0-9a-fA-F]+',
    'uuid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
  ];

  private string $pattern;

  public function __construct(string $pattern) {
    $this->pattern = $pattern;
  }

  public function apply(string $path): ?PathRewriteResult {
    $patternComponents = $this->compile();
    $pathComponents = $this->splitPath($path);
    $patternCount = count($patternComponents);
    $pathCount = count($pathComponents);

    // Not enough path components to match pattern
    if ($pathCount < $patternCount) {
      return null;
    }

    $params = [];
    $rewrittenComponents = [];

    // Match each pattern component against corresponding path component
    for ($i = 0; $i < $patternCount; $i++) {
      $patternComponent = $patternComponents[$i];
      $pathComponent = $pathComponents[$i];

      if (!preg_match($patternComponent['regex'], $pathComponent, $matches)) {
        return null;
      }

      // Collect params from this component
      foreach ($patternComponent['paramNames'] as $name) {
        $params[$name] = $matches[$name];
      }

      $rewrittenComponents[] = $patternComponent['rewritten'];
    }

    // Remaining path components become suffix
    $suffixComponents = array_slice($pathComponents, $patternCount);

    // Build result path
    $resultPath = '/' . implode('/', array_merge($rewrittenComponents, $suffixComponents));

    return new PathRewriteResult($resultPath, $params);
  }

  /**
   * Split path into components, filtering empty ones
   * @return array<string>
   */
  private function splitPath(string $path): array {
    return array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));
  }

  /**
   * Compile pattern into per-component regex patterns
   * @return array<array{regex: string, rewritten: string, paramNames: array<string>}>
   */
  private function compile(): array {
    $components = $this->splitPath($this->pattern);
    $result = [];
    $allParamNames = [];

    foreach ($components as $component) {
      $compiled = $this->compileComponent($component);
      foreach ($compiled['paramNames'] as $name) {
        if (in_array($name, $allParamNames, true)) {
          throw new \InvalidArgumentException("Duplicate parameter name: {$name}");
        }
        $allParamNames[] = $name;
      }
      $result[] = $compiled;
    }

    return $result;
  }

  /**
   * Compile a single component
   * @return array{regex: string, rewritten: string, paramNames: array<string>}
   */
  private function compileComponent(string $component): array {
    $paramNames = [];
    $regex = '';
    $rewritten = '';
    $offset = 0;

    // Match placeholders like {name} or {name:type}
    $placeholderPattern = '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([a-zA-Z]+))?\}#';

    while (preg_match($placeholderPattern, $component, $match, PREG_OFFSET_CAPTURE, $offset)) {
      $fullMatch = $match[0][0];
      $matchPos = $match[0][1];
      $name = $match[1][0];
      $type = $match[2][0] ?? null;

      // Add literal part before this placeholder
      $literal = substr($component, $offset, $matchPos - $offset);
      $regex .= preg_quote($literal, '#');
      $rewritten .= $literal;

      // Add placeholder pattern
      $resolvedType = $type ?? 'any';
      if (!isset(self::TYPE_PATTERNS[$resolvedType])) {
        throw new \InvalidArgumentException("Unknown placeholder type: {$resolvedType}");
      }
      $typePattern = self::TYPE_PATTERNS[$resolvedType];
      $regex .= '(?P<' . $name . '>' . $typePattern . ')';
      $rewritten .= $name;

      $paramNames[] = $name;
      $offset = $matchPos + strlen($fullMatch);
    }

    // Add remaining literal part
    $literal = substr($component, $offset);
    $regex .= preg_quote($literal, '#');
    $rewritten .= $literal;

    return [
      'regex' => '#\A' . $regex . '\z#u',
      'rewritten' => $rewritten,
      'paramNames' => $paramNames,
    ];
  }
}
