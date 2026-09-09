<?php
declare(strict_types=1);

use Coroq\Router\MapRouter;
use PHPUnit\Framework\TestCase;

class MapRouterTest extends TestCase {
  /**
   * Test basic routing with flat route maps
   */
  public function testBasicRouting(): void {
    $router = new MapRouter([
      'p1',
      'p2',
      'a' => 'p3',
    ]);
    
    // Successful route with string value
    $this->assertSame(['p1', 'p2', 'p3'], $router->route(['a']));
    
    // Non-existent route should throw RouteNotFoundException
    $this->expectException(\Coroq\Router\RouteNotFoundException::class);
    $router->route(['b']);
  }

  /**
   * Test nested routes with one level of nesting
   */
  public function testNestedRouting(): void {
    $router = new MapRouter([
      'p1',
      'p2',
      'a' => [
        'p3',
        'b' => 'p4',
      ],
    ]);
    
    // Successful nested route
    $this->assertSame(['p1', 'p2', 'p3', 'p4'], $router->route(['a', 'b']));
  }
  
  /**
   * Test that non-existent nested segment throws exception
   */
  public function testNonExistentNestedSegment(): void {
    $router = new MapRouter([
      'p1',
      'p2',
      'a' => [
        'p3',
        'b' => 'p4',
      ],
    ]);
    
    // Non-existent nested segment should throw RouteNotFoundException
    $this->expectException(\Coroq\Router\RouteNotFoundException::class);
    $router->route(['a', 'c']);
  }

  /**
   * Test routing with empty string keys in nested maps
   */
  public function testEmptyStringKeysInNestedMap(): void {
    $router = new MapRouter([
      'a' => [
        'p1',
        '' => 'p2',
      ],
    ]);
    $this->assertSame(['p1', 'p2'], $router->route(['a']));
  }

  /**
   * Test routing with multiple branches at the same level
   */
  public function testMultipleBranches(): void {
    $router = new MapRouter([
      'p1',
      'p2',
      'a' => [
        'p3',
        'c' => 'p6',
      ],
      'b' => 'p5',
    ]);
    
    // Test different branches at root level
    $this->assertSame(['p1', 'p2', 'p5'], $router->route(['b']));
    
    // Test nested branch
    $this->assertSame(['p1', 'p2', 'p3', 'p6'], $router->route(['a', 'c']));
  }
  
  /**
   * Test routing with empty map
   */
  public function testEmptyMap(): void {
    $emptyRouter = new MapRouter([]);
    $this->expectException(\Coroq\Router\RouteNotFoundException::class);
    $emptyRouter->route(['a']);
  }
  
  /**
   * Test deeply nested routes (4 levels deep)
   */
  public function testDeeplyNestedRoutes(): void {
    $deepRouter = new MapRouter([
      'a' => [
        'b' => [
          'c' => [
            'd' => 'deep',
          ],
        ],
      ],
    ]);
    $this->assertSame(['deep'], $deepRouter->route(['a', 'b', 'c', 'd']));
  }
  
  /**
   * Test routing with empty segments
   */
  public function testRootRouting(): void {
    $router = new MapRouter([
      'p1',
      '' => 'root-handler',
    ]);

    $this->assertSame(['p1', 'root-handler'], $router->route([]));
  }
  
  /**
   * Test collecting numeric keys from multiple nesting levels
   */
  public function testMultiLevelNumericKeys(): void {
    // Router with numeric keys at multiple nesting levels
    $router = new MapRouter([
      'n1', 
      'n2',
      'a' => [
        's1',
        's2',
        'b' => [
          'd1',
          'd2',
          'c' => 'final'
        ]
      ]
    ]);
    
    // Test collecting all numeric values along the path
    $this->assertSame(['n1', 'n2', 's1', 's2', 'd1', 'd2', 'final'], 
      $router->route(['a', 'b', 'c']));
  }

  /**
   * Mock router for testing delegation
   */
  private function createMockRouter(array $results): \Coroq\Router\RouterInterface {
    $mock = $this->createMock(\Coroq\Router\RouterInterface::class);
    $mock->method('route')->willReturn($results);
    return $mock;
  }

  /**
   * Test routing with RouterInterface objects in the map
   */
  public function testRouterInterfaceDelegation(): void {
    $mockRouter = $this->createMockRouter(['mock-result']);
    
    $router = new MapRouter([
      'middleware1',
      'a' => $mockRouter,
    ]);
    
    // Should delegate to the mock router and merge results
    $this->assertSame(['middleware1', 'mock-result'], $router->route(['a']));
  }

  /**
   * Test routing with RouterInterface at various nesting levels
   */
  public function testNestedRouterInterfaceDelegation(): void {
    $mockRouter1 = $this->createMockRouter(['result-1']);
    $mockRouter2 = $this->createMockRouter(['result-2']);
    
    $router = new MapRouter([
      'common-middleware',
      'a' => [
        'a-middleware',
        'b' => $mockRouter1,
        'c' => [
          'c-middleware',
          'd' => $mockRouter2,
        ],
      ],
    ]);
    
    // Test delegation at second level
    $this->assertSame(['common-middleware', 'a-middleware', 'result-1'], 
      $router->route(['a', 'b']));
    
    // Test delegation at third level
    $this->assertSame(['common-middleware', 'a-middleware', 'c-middleware', 'result-2'], 
      $router->route(['a', 'c', 'd']));
  }

  /**
   * Test routing with RouterInterface overriding further processing
   */
  public function testRouterInterfaceEarlyExit(): void {
    $mockRouter = $this->createMockRouter(['delegate-result']);
    
    // Create a router where a RouterInterface is placed before a potential match
    $router = new MapRouter([
      'middleware1',
      'a' => [
        $mockRouter,
        'b' => 'this-should-not-be-reached',
      ],
    ]);
    
    // Should delegate to the mock router and not continue to 'b'
    // since RouterInterface delegation takes precedence
    $this->assertSame(['middleware1', 'delegate-result'], $router->route(['a', 'b']));
  }

  private function createSkippingRouter(): \Coroq\Router\RouterInterface {
    $mock = $this->createMock(\Coroq\Router\RouterInterface::class);
    $mock->method('route')->will($this->throwException(new \Coroq\Router\RouteSkipException()));
    return $mock;
  }

  public function testRouteSkipping(): void {
    $skippingRouter = $this->createSkippingRouter();
    
    $router = new MapRouter([
      'a' => [
        $skippingRouter,
        '' => 'result',
      ],
    ]);
    
    $this->assertSame(['result'], $router->route(['a']));
  }

  public function testAllRoutesSkipped(): void {
    $skippingRouter1 = $this->createSkippingRouter();
    $skippingRouter2 = $this->createSkippingRouter();
    
    $router = new MapRouter([
      'a' => [
        $skippingRouter1,
        $skippingRouter2,
      ],
    ]);
    
    $this->expectException(\Coroq\Router\RouteNotFoundException::class);
    $router->route(['a']);
  }

  public function testNestedRouteSkipping(): void {
    $skippingRouter = $this->createSkippingRouter();
    
    $router = new MapRouter([
      'm1',
      'a' => [
        'inner1',
        'b' => [
          $skippingRouter,
          '' => 'result-after-skip',
        ],
        'c' => 'alternative-route',
      ],
    ]);
    
    $this->assertSame(['m1', 'inner1', 'result-after-skip'], $router->route(['a', 'b']));
  }

  public function testNumericKeySkipping(): void {
    $skippingRouter = $this->createSkippingRouter();

    $router = new MapRouter([
      'm1',
      $skippingRouter,
      'm2',
      'a' => 'endpoint',
    ]);

    $this->assertSame(['m1', 'm2', 'endpoint'], $router->route(['a']));
  }

  public function testNonStringSegmentThrowsException(): void {
    $router = new MapRouter([
      'a' => 'handler',
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $router->route([123]);
  }

  public function testStringKeyRouterInterfaceDelegation(): void {
    $mockRouter = $this->createMockRouter(['delegated-result']);

    $router = new MapRouter([
      'middleware',
      'a' => $mockRouter,
    ]);

    // RouterInterface at string key should delegate with remaining segments
    $this->assertSame(['middleware', 'delegated-result'], $router->route(['a', 'b', 'c']));
  }

  /**
   * Test that a matched scalar value is dropped when the path continues below it
   */
  public function testScalarValueIsNotIncludedWhenPathContinues(): void {
    $fallbackRouter = $this->createMockRouter(['fallback-result']);

    $router = new MapRouter([
      'a' => 'a-handler',
      $fallbackRouter,
    ]);

    $this->assertSame(['fallback-result'], $router->route(['a', 'b']));
    $this->assertSame(['a-handler'], $router->route(['a']));
  }
}
