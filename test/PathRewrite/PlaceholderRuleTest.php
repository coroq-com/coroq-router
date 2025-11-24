<?php
declare(strict_types=1);

namespace Coroq\Router\PathRewrite;

use PHPUnit\Framework\TestCase;

class PlaceholderRuleTest extends TestCase {
  public function testSimplePlaceholder(): void {
    $rule = new PlaceholderRule('/user/{id}');
    $result = $rule->apply('/user/123');

    $this->assertNotNull($result);
    $this->assertSame('/user/id', $result->path);
    $this->assertSame(['id' => '123'], $result->params);
  }

  public function testNoMatch(): void {
    $rule = new PlaceholderRule('/user/{id}');
    $result = $rule->apply('/post/123');

    $this->assertNull($result);
  }

  public function testIntType(): void {
    $rule = new PlaceholderRule('/user/{id:int}');

    $result = $rule->apply('/user/123');
    $this->assertNotNull($result);
    $this->assertSame(['id' => '123'], $result->params);

    $result = $rule->apply('/user/abc');
    $this->assertNull($result);
  }

  public function testAlphaType(): void {
    $rule = new PlaceholderRule('/page/{name:alpha}');

    $result = $rule->apply('/page/about');
    $this->assertNotNull($result);
    $this->assertSame('/page/name', $result->path);
    $this->assertSame(['name' => 'about'], $result->params);

    // Per-component matching: 'about123' is not fully alpha, no match
    $result = $rule->apply('/page/about123');
    $this->assertNull($result);

    $result = $rule->apply('/page/123about');
    $this->assertNull($result);
  }

  public function testAlnumType(): void {
    $rule = new PlaceholderRule('/item/{code:alnum}');

    $result = $rule->apply('/item/abc123');
    $this->assertNotNull($result);
    $this->assertSame(['code' => 'abc123'], $result->params);

    // Per-component matching: 'abc-123' contains '-', not fully alnum, no match
    $result = $rule->apply('/item/abc-123');
    $this->assertNull($result);
  }

  public function testHexType(): void {
    $rule = new PlaceholderRule('/token/{value:hex}');

    $result = $rule->apply('/token/5f3a');
    $this->assertNotNull($result);
    $this->assertSame(['value' => '5f3a'], $result->params);

    $result = $rule->apply('/token/5F3A');
    $this->assertNotNull($result);

    // Per-component matching: '5g3a' contains 'g', not fully hex, no match
    $result = $rule->apply('/token/5g3a');
    $this->assertNull($result);
  }

  public function testUuidType(): void {
    $rule = new PlaceholderRule('/item/{id:uuid}');

    $result = $rule->apply('/item/550e8400-e29b-41d4-a716-446655440000');
    $this->assertNotNull($result);
    $this->assertSame(['id' => '550e8400-e29b-41d4-a716-446655440000'], $result->params);

    $result = $rule->apply('/item/550e8400e29b41d4a716446655440000');
    $this->assertNull($result);

    $result = $rule->apply('/item/not-a-uuid');
    $this->assertNull($result);
  }

  public function testMultiplePlaceholders(): void {
    $rule = new PlaceholderRule('/user/{userid:int}/post/{postid:int}');
    $result = $rule->apply('/user/42/post/99');

    $this->assertNotNull($result);
    $this->assertSame('/user/userid/post/postid', $result->path);
    $this->assertSame(['userid' => '42', 'postid' => '99'], $result->params);
  }

  public function testMultiplePlaceholdersInOneSegment(): void {
    $rule = new PlaceholderRule('/file/{name}.{ext}');
    $result = $rule->apply('/file/report.pdf');

    $this->assertNotNull($result);
    $this->assertSame('/file/name.ext', $result->path);
    $this->assertSame(['name' => 'report', 'ext' => 'pdf'], $result->params);
  }

  public function testDatePattern(): void {
    $rule = new PlaceholderRule('/archive/{year:int}-{month:int}-{day:int}');
    $result = $rule->apply('/archive/2025-01-15');

    $this->assertNotNull($result);
    $this->assertSame('/archive/year-month-day', $result->path);
    $this->assertSame(['year' => '2025', 'month' => '01', 'day' => '15'], $result->params);
  }

  public function testUnknownTypeThrowsException(): void {
    $rule = new PlaceholderRule('/item/{id:unknown}');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Unknown placeholder type: unknown');
    $rule->apply('/item/any-value-here');
  }

  public function testDuplicateParameterNameThrowsException(): void {
    $rule = new PlaceholderRule('/user/{id}/post/{id}');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Duplicate parameter name: id');
    $rule->apply('/user/1/post/2');
  }

  public function testPrefixMatch(): void {
    $rule = new PlaceholderRule('/user/{id}');

    // Prefix matching: matches /user/123, '/extra' becomes suffix
    $result = $rule->apply('/user/123/extra');
    $this->assertNotNull($result);
    $this->assertSame('/user/id/extra', $result->path);
    $this->assertSame(['id' => '123'], $result->params);

    // Start anchor still works - prefix must match from beginning
    $result = $rule->apply('/prefix/user/123');
    $this->assertNull($result);
  }

  public function testLiteralPath(): void {
    $rule = new PlaceholderRule('/about');
    $result = $rule->apply('/about');

    $this->assertNotNull($result);
    $this->assertSame('/about', $result->path);
    $this->assertSame([], $result->params);
  }

  public function testPrefixBeforePlaceholder(): void {
    $rule = new PlaceholderRule('/prefix{x}');
    $result = $rule->apply('/prefixvalue');

    $this->assertNotNull($result);
    $this->assertSame('/prefixx', $result->path);
    $this->assertSame(['x' => 'value'], $result->params);
  }

  public function testSuffixAfterPlaceholder(): void {
    $rule = new PlaceholderRule('/{x}suffix');
    $result = $rule->apply('/valuesuffix');

    $this->assertNotNull($result);
    $this->assertSame('/xsuffix', $result->path);
    $this->assertSame(['x' => 'value'], $result->params);
  }

  public function testPrefixAndSuffix(): void {
    $rule = new PlaceholderRule('/prefix{x}suffix');
    $result = $rule->apply('/prefixvaluesuffix');

    $this->assertNotNull($result);
    $this->assertSame('/prefixxsuffix', $result->path);
    $this->assertSame(['x' => 'value'], $result->params);
  }

  public function testAdjacentPlaceholders(): void {
    $rule = new PlaceholderRule('/{a}{b}');
    $result = $rule->apply('/xy');

    $this->assertNotNull($result);
    $this->assertSame('/ab', $result->path);
    $this->assertSame(['a' => 'x', 'b' => 'y'], $result->params);
  }

  public function testPlaceholderAtStart(): void {
    $rule = new PlaceholderRule('{x}/path');
    $result = $rule->apply('value/path');

    $this->assertNotNull($result);
    $this->assertSame('/x/path', $result->path);
    $this->assertSame(['x' => 'value'], $result->params);
  }

  public function testPlaceholderAtEnd(): void {
    $rule = new PlaceholderRule('/path/{x}');
    $result = $rule->apply('/path/value');

    $this->assertNotNull($result);
    $this->assertSame('/path/x', $result->path);
    $this->assertSame(['x' => 'value'], $result->params);
  }

  public function testOnlyPlaceholder(): void {
    $rule = new PlaceholderRule('{x}');
    $result = $rule->apply('value');

    $this->assertNotNull($result);
    $this->assertSame('/x', $result->path);
    $this->assertSame(['x' => 'value'], $result->params);
  }

  public function testPlaceholderWithUnderscore(): void {
    $rule = new PlaceholderRule('/{my_var}');
    $result = $rule->apply('/value');

    $this->assertNotNull($result);
    $this->assertSame('/my_var', $result->path);
    $this->assertSame(['my_var' => 'value'], $result->params);
  }

  public function testPlaceholderWithNumbers(): void {
    $rule = new PlaceholderRule('/{var1}/{var2}');
    $result = $rule->apply('/a/b');

    $this->assertNotNull($result);
    $this->assertSame('/var1/var2', $result->path);
    $this->assertSame(['var1' => 'a', 'var2' => 'b'], $result->params);
  }

  public function testTypedAdjacentPlaceholders(): void {
    $rule = new PlaceholderRule('/{a:int}{b:alpha}');

    $result = $rule->apply('/123abc');
    $this->assertNotNull($result);
    $this->assertSame(['a' => '123', 'b' => 'abc'], $result->params);

    $result = $rule->apply('/abc123');
    $this->assertNull($result);
  }

  public function testEmptyPattern(): void {
    $rule = new PlaceholderRule('');
    $result = $rule->apply('');

    $this->assertNotNull($result);
    $this->assertSame('/', $result->path);
    $this->assertSame([], $result->params);
  }

  public function testSpecialRegexCharsInLiteral(): void {
    $rule = new PlaceholderRule('/path.with" "special+chars/{id}');
    $result = $rule->apply('/path.with" "special+chars/123');

    $this->assertNotNull($result);
    $this->assertSame('/path.with" "special+chars/id', $result->path);
    $this->assertSame(['id' => '123'], $result->params);
  }

  public function testExplicitAnyType(): void {
    $rule = new PlaceholderRule('/{id:any}');
    $result = $rule->apply('/hello-world_123');

    $this->assertNotNull($result);
    $this->assertSame(['id' => 'hello-world_123'], $result->params);
  }

  public function testDoubleBraces(): void {
    $rule = new PlaceholderRule('/{{x}}');
    $result = $rule->apply('/{value}');

    $this->assertNotNull($result);
    $this->assertSame('/{x}', $result->path);
    $this->assertSame(['x' => 'value'], $result->params);
  }
}
