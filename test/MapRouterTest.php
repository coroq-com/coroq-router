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
    $this->assertSame(['p1', 'p2', 'p3'], $router->routePath('/a'));
    
    // Non-existent route should throw RouteNotFoundException
    $this->expectException(\Coroq\Router\RouteNotFoundException::class);
    $router->routePath('/b');
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
    $this->assertSame(['p1', 'p2', 'p3', 'p4'], $router->routePath('/a/b'));
  }
  
  /**
   * Test that non-existent nested waypoint throws exception
   */
  public function testNonExistentNestedWaypoint(): void {
    $router = new MapRouter([
      'p1',
      'p2',
      'a' => [
        'p3',
        'b' => 'p4',
      ],
    ]);
    
    // Non-existent nested waypoint should throw RouteNotFoundException
    $this->expectException(\Coroq\Router\RouteNotFoundException::class);
    $router->routePath('/a/c');
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
    $this->assertSame(['p1', 'p2'], $router->routePath('/a'));
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
    $this->assertSame(['p1', 'p2', 'p5'], $router->routePath('/b'));
    
    // Test nested branch
    $this->assertSame(['p1', 'p2', 'p3', 'p6'], $router->routePath('/a/c'));
  }
  
  /**
   * Test routing with empty map
   */
  public function testEmptyMap(): void {
    $emptyRouter = new MapRouter([]);
    $this->expectException(\Coroq\Router\RouteNotFoundException::class);
    $emptyRouter->routePath('/a');
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
    $this->assertSame(['deep'], $deepRouter->routePath('/a/b/c/d'));
  }
  
  /**
   * Test path normalization (leading/trailing slashes and empty paths)
   */
  public function testPathNormalization(): void {
    // Test root path
    $rootRouter = new MapRouter([
      'p1',
      '' => 'root-handler',
    ]);
    
    // Empty path and slash should both route to root
    $this->assertSame(['p1', 'root-handler'], $rootRouter->routePath(''));
    $this->assertSame(['p1', 'root-handler'], $rootRouter->routePath('/'));
    
    // Test trailing slashes
    $router = new MapRouter([
      'a' => [
        'b' => 'handler',
      ],
    ]);
    
    // Path with and without trailing slash should route the same
    $this->assertSame(['handler'], $router->routePath('/a/b'));
    $this->assertSame(['handler'], $router->routePath('/a/b/'));
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
      $router->routePath('/a/b/c'));
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
    $this->assertSame(['middleware1', 'mock-result'], $router->routePath('/a'));
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
      $router->routePath('/a/b'));
    
    // Test delegation at third level
    $this->assertSame(['common-middleware', 'a-middleware', 'c-middleware', 'result-2'], 
      $router->routePath('/a/c/d'));
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
    $this->assertSame(['middleware1', 'delegate-result'], $router->routePath('/a/b'));
  }
}
