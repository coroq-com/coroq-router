<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

use PHPUnit\Framework\TestCase;

class PathStringRuleTest extends TestCase {
  private function makeRule(): PathStringRule {
    return new class extends PathStringRule {
      protected function applyToPath(string $path): ?PathRewriteResult {
        if (!preg_match('#\A/legacy/(.+)/page([0-9]+)\z#', $path, $matches)) {
          return null;
        }
        return PathRewriteResult::fromPath('/legacy/item/page', [
          'item' => $matches[1],
          'page' => $matches[2],
        ]);
      }
    };
  }

  public function testAppliesRuleToPathString(): void {
    $result = $this->makeRule()->apply(['legacy', 'foo', 'bar', 'page12']);

    $this->assertNotNull($result);
    $this->assertSame(['legacy', 'item', 'page'], $result->segments);
    $this->assertSame(['item' => 'foo/bar', 'page' => '12'], $result->params);
  }

  public function testReturnsNullWhenPathDoesNotMatch(): void {
    $this->assertNull($this->makeRule()->apply(['users', 'detail']));
  }

  public function testReturnsNullWhenSegmentContainsSlash(): void {
    $this->assertNull($this->makeRule()->apply(['legacy', 'a/b', 'page12']));
  }
}
