<?php
declare(strict_types=1);

use Coroq\Router\MapRouter;
use Coroq\Router\RouteNotFoundException;
use Coroq\Router\RouteSkipException;
use Coroq\Router\RouterInterface;
use Coroq\Router\SegmentRouter;
use PHPUnit\Framework\TestCase;

class SegmentRouterTest extends TestCase {
  public function testMatchesSegmentGivenAsIntOrString(): void {
    $this->assertSame(['handler'], (new SegmentRouter(2026, 'handler'))->route(['2026']));
    $this->assertSame(['handler'], (new SegmentRouter('2026', 'handler'))->route(['2026']));
  }

  public function testSkipsWhenSegmentDoesNotMatch(): void {
    $this->expectException(RouteSkipException::class);
    (new SegmentRouter('users', 'handler'))->route(['posts']);
  }

  public function testSkipsWhenPathContinuesBelowAValueThatIsNotARouter(): void {
    $this->expectException(RouteSkipException::class);
    (new SegmentRouter('users', 'handler'))->route(['users', 'detail']);
  }

  public function testRoutesIntoANestedMap(): void {
    $router = new SegmentRouter(2026, [
      'middleware',
      '' => 'year-handler',
      'summary' => 'summary-handler',
    ]);

    $this->assertSame(['middleware', 'year-handler'], $router->route(['2026']));
    $this->assertSame(['middleware', 'summary-handler'], $router->route(['2026', 'summary']));
  }

  public function testDelegatesToARouterWithTheRemainingSegments(): void {
    $delegate = $this->createMock(RouterInterface::class);
    $delegate->expects($this->once())->method('route')->with(['a', 'b'])->willReturn(['delegated']);

    $this->assertSame(['delegated'], (new SegmentRouter('docs', $delegate))->route(['docs', 'a', 'b']));
  }

  /**
   * Test that a route not found below the segment is reported as the whole path
   */
  public function testReportsThePathFromItsOwnSegmentDown(): void {
    $router = new SegmentRouter('a', ['b' => ['c' => 'handler']]);

    try {
      $router->route(['a', 'b', 'x']);
      $this->fail('RouteNotFoundException was not thrown');
    }
    catch (RouteNotFoundException $exception) {
      $this->assertSame('No route for /a/b/x', $exception->getMessage());
      $this->assertSame(['a', 'b', 'x'], $exception->getSegments());
    }
  }

  /**
   * Test the case SegmentRouter exists for: a segment PHP can not keep as a string key
   */
  public function testRoutesNumericSegmentsInAMap(): void {
    $router = new MapRouter([
      'archive' => [
        new SegmentRouter(2026, 'year-2026'),
        new SegmentRouter(2025, 'year-2025'),
        'latest' => 'latest',
      ],
    ]);

    $this->assertSame(['year-2026'], $router->route(['archive', '2026']));
    $this->assertSame(['year-2025'], $router->route(['archive', '2025']));
    $this->assertSame(['latest'], $router->route(['archive', 'latest']));

    $this->expectException(RouteNotFoundException::class);
    $router->route(['archive', '2024']);
  }
}
