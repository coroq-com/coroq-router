<?php
declare(strict_types=1);

use Coroq\Router\Path;
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
}
