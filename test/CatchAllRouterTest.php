<?php
declare(strict_types=1);

use Coroq\Router\CatchAllRouter;
use PHPUnit\Framework\TestCase;

class CatchAllRouterTest extends TestCase {
  /**
   * Test that CatchAll always returns the same result regardless of waypoints
   */
  public function testAlwaysReturnsSameResult(): void {
    $catchAll = new CatchAllRouter('fixed-result');
    
    // Should return the same result for any waypoints
    $this->assertSame(['fixed-result'], $catchAll->route([]));
    $this->assertSame(['fixed-result'], $catchAll->route(['a']));
    $this->assertSame(['fixed-result'], $catchAll->route(['a', 'b', 'c']));
  }
}
