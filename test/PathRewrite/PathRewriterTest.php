<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

use PHPUnit\Framework\TestCase;

class PathRewriterTest extends TestCase {
  public function testRewriteWithStringRule(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRule('/user/{id:int}');

    $result = $rewriter->rewrite('/user/123');

    $this->assertSame('/user/id', $result->path);
    $this->assertSame(['id' => '123'], $result->params);
  }

  public function testRewriteWithRuleObject(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRule(new PlaceholderRule('/user/{id:int}'));

    $result = $rewriter->rewrite('/user/123');

    $this->assertSame('/user/id', $result->path);
    $this->assertSame(['id' => '123'], $result->params);
  }

  public function testAddRules(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRules([
      '/user/{id:int}',
      '/post/{slug}',
    ]);

    $result = $rewriter->rewrite('/user/123');
    $this->assertSame('/user/id', $result->path);

    $result = $rewriter->rewrite('/post/hello-world');
    $this->assertSame('/post/slug', $result->path);
  }

  public function testSequentialRulesApply(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRules([
      '/user/{userid:int}/',
      '/user/userid/{action:alpha}',
    ]);

    // First rule: /user/6755/edit -> /user/userid/edit, params: [userid=>6755]
    // Second rule: /user/userid/edit -> /user/userid/action, params: [action=>edit]
    $result = $rewriter->rewrite('/user/6755/edit');
    $this->assertSame('/user/userid/action', $result->path);
    $this->assertSame(['userid' => '6755', 'action' => 'edit'], $result->params);
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
    $rewriter->rewrite('/item/5f3a');
  }

  public function testOnlyMatchingRulesApply(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRules([
      '/user/{id:int}',
      '/post/{slug}',
    ]);

    $result = $rewriter->rewrite('/user/123');
    $this->assertSame('/user/id', $result->path);
    $this->assertSame(['id' => '123'], $result->params);
  }

  public function testNoMatchReturnsOriginalPath(): void {
    $rewriter = new PathRewriter();
    $rewriter->addRule('/user/{id:int}');

    $result = $rewriter->rewrite('/about');

    $this->assertSame('/about', $result->path);
    $this->assertSame([], $result->params);
  }

  public function testFluentInterface(): void {
    $rewriter = new PathRewriter();
    $result = $rewriter
      ->addRule('/user/{id:int}')
      ->addRule('/post/{slug}')
      ->rewrite('/user/123');

    $this->assertSame('/user/id', $result->path);
  }

  public function testEmptyRewriter(): void {
    $rewriter = new PathRewriter();
    $result = $rewriter->rewrite('/any/path');

    $this->assertSame('/any/path', $result->path);
    $this->assertSame([], $result->params);
  }
}
