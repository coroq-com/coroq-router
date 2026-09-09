<?php
declare(strict_types=1);

use Coroq\Router\Path;
use Coroq\Router\RouteNotFoundException;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase {
  public function testConvertsPathToSegments(): void {
    $this->assertSame(['users', 'detail'], Path::toSegments('/users/detail'));
    $this->assertSame(['users'], Path::toSegments('users'));
  }

  public function testIgnoresSurroundingAndRedundantSlashes(): void {
    $this->assertSame(['a', 'b'], Path::toSegments('/a/b/'));
    $this->assertSame(['a', 'b'], Path::toSegments('//a//b//'));
  }

  public function testConvertsRootPathToEmptySegments(): void {
    $this->assertSame([], Path::toSegments('/'));
    $this->assertSame([], Path::toSegments(''));
  }

  public function testDecodesPercentEncodedSegments(): void {
    $this->assertSame(['日本語'], Path::toSegments('/%E6%97%A5%E6%9C%AC%E8%AA%9E'));
    $this->assertSame(['john doe'], Path::toSegments('/john%20doe'));
  }

  public function testDecodesEncodedSlashWithoutSplittingSegment(): void {
    $this->assertSame(['a/b'], Path::toSegments('/a%2Fb'));
  }

  public function testKeepsSegmentThatLooksEmpty(): void {
    $this->assertSame(['0'], Path::toSegments('/0'));
  }

  public function testConvertsSegmentsToPath(): void {
    $this->assertSame('/users/detail', Path::fromSegments(['users', 'detail']));
    $this->assertSame('/', Path::fromSegments([]));
  }

  public function testEncodesSegmentsWhenConvertingToPath(): void {
    $this->assertSame('/%E6%97%A5%E6%9C%AC%E8%AA%9E', Path::fromSegments(['日本語']));
    $this->assertSame('/a%2Fb', Path::fromSegments(['a/b']));
  }

  public function testRemovesBasePath(): void {
    $this->assertSame(['users', 'detail'], Path::removeBasePath('/system/survey', ['system', 'survey', 'users', 'detail']));
    $this->assertSame(['users'], Path::removeBasePath('/system/survey', ['system', 'survey', 'users']));
  }

  public function testIgnoresSurroundingAndRedundantSlashesInBasePath(): void {
    $this->assertSame(['users'], Path::removeBasePath('/system/survey/', ['system', 'survey', 'users']));
    $this->assertSame(['users'], Path::removeBasePath('system/survey', ['system', 'survey', 'users']));
    $this->assertSame(['users'], Path::removeBasePath('//system//survey//', ['system', 'survey', 'users']));
  }

  public function testReturnsEmptySegmentsWhenSegmentsAreBasePathItself(): void {
    $this->assertSame([], Path::removeBasePath('/system/survey', ['system', 'survey']));
  }

  public function testReturnsSegmentsWhenBasePathIsEmpty(): void {
    $this->assertSame(['users', 'detail'], Path::removeBasePath('', ['users', 'detail']));
    $this->assertSame(['users', 'detail'], Path::removeBasePath('/', ['users', 'detail']));
    $this->assertSame([], Path::removeBasePath('/', []));
  }

  public function testThrowsWhenSegmentsAreNotUnderBasePath(): void {
    $this->expectException(RouteNotFoundException::class);
    Path::removeBasePath('/system/survey', ['other', 'users']);
  }

  public function testThrowsWhenSegmentOnlyPartiallyMatchesBasePathSegment(): void {
    $this->expectException(RouteNotFoundException::class);
    Path::removeBasePath('/system/survey', ['system', 'surveyxyz', 'users']);
  }

  public function testThrowsWhenSegmentsAreShorterThanBasePath(): void {
    $this->expectException(RouteNotFoundException::class);
    Path::removeBasePath('/system/survey', ['system']);
  }

  public function testThrowsWhenSegmentsAreEmptyButBasePathIsNot(): void {
    $this->expectException(RouteNotFoundException::class);
    Path::removeBasePath('/system/survey', []);
  }
}
