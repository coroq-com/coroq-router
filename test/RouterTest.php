<?php
declare(strict_types=1);

use Coroq\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase {
  /**
   * Test basic routing with flat route maps
   */
  public function testBasicRouting(): void {
    $router = new Router([
      'p1',
      'p2',
      'a' => 'p3',
    ]);
    
    // Successful route with string value
    $this->assertSame(['p1', 'p2', 'p3'], $router->route(['a']));
    
    // Non-existent route
    $this->assertSame([], $router->route(['b']));
  }

  /**
   * Test nested routes with one level of nesting
   */
  public function testNestedRouting(): void {
    $router = new Router([
      'p1',
      'p2',
      'a' => [
        'p3',
        'b' => 'p4',
      ],
    ]);
    
    // Successful nested route
    $this->assertSame(['p1', 'p2', 'p3', 'p4'], $router->route(['a', 'b']));
    
    // Non-existent nested waypoint
    $this->assertSame([], $router->route(['a', 'c']));
  }

  /**
   * Test routing with empty string keys in nested maps
   */
  public function testEmptyStringKeysInNestedMap(): void {
    $router = new Router([
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
    $router = new Router([
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
    $emptyRouter = new Router([]);
    $this->assertSame([], $emptyRouter->route(['a']));
  }
  
  /**
   * Test deeply nested routes (4 levels deep)
   */
  public function testDeeplyNestedRoutes(): void {
    $deepRouter = new Router([
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
   * Test input validation handling - empty waypoints
   */
  public function testEmptyWaypoints(): void {
    $router = new Router(['a' => 'value']);
    
    $this->assertSame([], $router->route([]));
  }
  
  /**
   * Test input validation handling - non-string waypoints
   */
  public function testNonStringWaypoints(): void {
    $router = new Router(['a' => 'value']);
    
    $this->expectException(InvalidArgumentException::class);
    $router->route([123]);
  }
  
  /**
   * Test collecting numeric keys from multiple nesting levels
   */
  public function testMultiLevelNumericKeys(): void {
    // Router with numeric keys at multiple nesting levels
    $router = new Router([
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
   * Test empty string keys at the root level
   */
  public function testEmptyStringKeysAtRootLevel(): void {
    // Router with an empty string key at root level
    $router = new Router([
      'p1',
      '' => 'empty-key-value',
      'a' => 'regular-key-value'
    ]);
    
    // Empty string should match when we route with an empty string waypoint
    $this->assertSame(['p1', 'empty-key-value'], $router->route(['']));
    
    // Regular routing should still work
    $this->assertSame(['p1', 'regular-key-value'], $router->route(['a']));
  }
}