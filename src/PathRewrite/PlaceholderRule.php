<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

class PlaceholderRule implements PathRewriteRuleInterface {
  private const TYPE_PATTERNS = [
    'any' => '.+',
    'int' => '[0-9]+',
    'alpha' => '[a-zA-Z]+',
    'alnum' => '[a-zA-Z0-9]+',
    'hex' => '[0-9a-fA-F]+',
    'uuid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
  ];

  /** @var list<array{regex: string, rewritten: string, paramNames: list<string>}> */
  private array $patternSegments;

  public function __construct(string $pattern) {
    $this->patternSegments = $this->compile($pattern);
  }

  public function apply(array $segments): ?PathRewriteResult {
    $patternSegments = $this->patternSegments;
    $patternCount = count($patternSegments);

    // Not enough segments to match pattern
    if (count($segments) < $patternCount) {
      return null;
    }

    $params = [];
    $rewrittenSegments = [];

    // Match each pattern segment against corresponding segment
    for ($i = 0; $i < $patternCount; $i++) {
      $patternSegment = $patternSegments[$i];

      if (!preg_match($patternSegment['regex'], $segments[$i], $matches)) {
        return null;
      }

      // Collect params from this segment
      foreach ($patternSegment['paramNames'] as $name) {
        $params[$name] = $matches[$name];
      }

      $rewrittenSegments[] = $patternSegment['rewritten'];
    }

    // Remaining segments are kept as they are
    $rest = array_slice($segments, $patternCount);

    return new PathRewriteResult(array_merge($rewrittenSegments, $rest), $params);
  }

  /**
   * Split the pattern into segments, filtering empty ones
   * @return list<string>
   */
  private function splitPattern(string $pattern): array {
    return array_values(array_filter(explode('/', $pattern), fn($s) => $s !== ''));
  }

  /**
   * Compile pattern into per-segment regex patterns
   * @return list<array{regex: string, rewritten: string, paramNames: list<string>}>
   */
  private function compile(string $pattern): array {
    $segments = $this->splitPattern($pattern);
    $result = [];
    $allParamNames = [];

    foreach ($segments as $segment) {
      $compiled = $this->compileSegment($segment);
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
   * Compile a single segment
   * @return array{regex: string, rewritten: string, paramNames: list<string>}
   */
  private function compileSegment(string $segment): array {
    $paramNames = [];
    $regex = '';
    $rewritten = '';
    $offset = 0;

    // Match placeholders like {name} or {name:type}
    $placeholderPattern = '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([a-zA-Z]+))?\}#';

    while (preg_match($placeholderPattern, $segment, $match, PREG_OFFSET_CAPTURE, $offset)) {
      $fullMatch = $match[0][0];
      $matchPos = $match[0][1];
      $name = $match[1][0];
      $type = $match[2][0] ?? null;

      // Add literal part before this placeholder
      $literal = substr($segment, $offset, $matchPos - $offset);
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
    $literal = substr($segment, $offset);
    $regex .= preg_quote($literal, '#');
    $rewritten .= $literal;

    return [
      'regex' => '#\A' . $regex . '\z#u',
      'rewritten' => $rewritten,
      'paramNames' => $paramNames,
    ];
  }
}
