<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

use PHPUnit\Framework\TestCase;

class PathRewriterTest extends TestCase {
  public function testRewriteWithStringRule(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRule('/user/{id:int}');

    [$segments, $params] = $rewriter->rewrite(['user', '123']);

    $this->assertSame(['user', 'id'], $segments);
    $this->assertSame(['id' => '123'], $params);
  }

  public function testRewriteWithRuleObject(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRule(new PlaceholderRule('/user/{id:int}'));

    [$segments, $params] = $rewriter->rewrite(['user', '123']);

    $this->assertSame(['user', 'id'], $segments);
    $this->assertSame(['id' => '123'], $params);
  }

  public function testAddRules(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRules([
      '/user/{id:int}',
      '/post/{slug}',
    ]);

    [$segments] = $rewriter->rewrite(['user', '123']);
    $this->assertSame(['user', 'id'], $segments);

    [$segments] = $rewriter->rewrite(['post', 'hello-world']);
    $this->assertSame(['post', 'slug'], $segments);
  }

  public function testSequentialRulesApply(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRules([
      '/user/{userid:int}/',
      '/user/userid/{action:alpha}',
    ]);

    // First rule: /user/6755/edit -> /user/userid/edit, params: [userid=>6755]
    // Second rule: /user/userid/edit -> /user/userid/action, params: [action=>edit]
    [$segments, $params] = $rewriter->rewrite(['user', '6755', 'edit']);
    $this->assertSame(['user', 'userid', 'action'], $segments);
    $this->assertSame(['userid' => '6755', 'action' => 'edit'], $params);
  }

  public function testDuplicateParamAcrossRulesThrowsException(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRules([
      '/item/{id:hex}',
      '/item/{id}',
    ]);

    // First rule matches, transforms to /item/id
    // Second rule also matches /item/id, tries to set id param again
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Duplicate parameter name across rules: id');
    $rewriter->rewrite(['item', '5f3a']);
  }

  public function testOnlyMatchingRulesApply(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRules([
      '/user/{id:int}',
      '/post/{slug}',
    ]);

    [$segments, $params] = $rewriter->rewrite(['user', '123']);
    $this->assertSame(['user', 'id'], $segments);
    $this->assertSame(['id' => '123'], $params);
  }

  public function testNoMatchReturnsOriginalPath(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRule('/user/{id:int}');

    [$segments, $params] = $rewriter->rewrite(['about']);

    $this->assertSame(['about'], $segments);
    $this->assertSame([], $params);
  }

  public function testFluentInterface(): void {
    $rewriter = new PathRewriter();
    [$segments] = $rewriter
      ->addRule('/user/{id:int}')
      ->addRule('/post/{slug}')
      ->rewrite(['user', '123']);

    $this->assertSame(['user', 'id'], $segments);
  }

  public function testEmptyRewriter(): void {
    $rewriter = new PathRewriter();
    [$segments, $params] = $rewriter->rewrite(['any', 'path']);

    $this->assertSame(['any', 'path'], $segments);
    $this->assertSame([], $params);
  }

  public function testRewriteWithPathStringRule(): void {
    $rule = new class extends PathStringRule {
      protected function applyToPath(string $path): ?PathRewriteResult {
        if (!preg_match('#\A/legacy/(.+)\z#', $path, $matches)) {
          return null;
        }
        return PathRewriteResult::fromPath('/user/' . $matches[1], []);
      }
    };

    $rewriter = new PathRewriter();
    $rewriter->addRule($rule);
    $rewriter->addRule('/user/{id:int}');

    [$segments, $params] = $rewriter->rewrite(['legacy', '123']);

    $this->assertSame(['user', 'id'], $segments);
    $this->assertSame(['id' => '123'], $params);
  }
}
