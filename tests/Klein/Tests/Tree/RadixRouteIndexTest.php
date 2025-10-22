<?php

namespace Klein\Tests\Tree;

use Klein\Routes\Route;
use Klein\Tree\RadixRouteIndex;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Test class for the `lookupByDFS` method in the `RadixRouteIndex` class.
 *
 * The `lookupByDFS` method is a private method in `RadixRouteIndex` that explores
 * the radix tree to collect all routes associated with a given prefix.
 */
class RadixRouteIndexTest extends TestCase
{
    /**
     * Tests that `lookupByDFS` returns an empty array
     * when no routes are associated with the given prefix.
     */
    public function testlookupByDFSReturnsEmptyForUndefinedPrefix(): void
    {
        $radixRouteIndex = new RadixRouteIndex();

        // Use reflection to access the private method
        $method = $this->getPrivateMethod('lookupByDFS', $radixRouteIndex);
        $result = $method->invokeArgs($radixRouteIndex, ['/undefined']);

        $this->assertEmpty($result, 'Expected an empty array for an undefined prefix');
    }

    /**
     * Tests that `lookupByDFS` correctly retrieves routes for a specific prefix.
     */
    public function testlookupByDFSFetchesRoutes(): void
    {
        $route = new Route(
            fn() => 'callback',
            '/users',
            'GET'
        );

        $radixTree = [
            '/users' => [
                $route->hash => $route
            ]
        ];

        $radixRouteIndex = new RadixRouteIndex($radixTree);

        // Use reflection to access the private method
        $method = $this->getPrivateMethod('lookupByDFS', $radixRouteIndex);
        $result = $method->invokeArgs($radixRouteIndex, ['/users']);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey($route->hash, $result);
        $this->assertSame($route, $result[$route->hash]);
    }

    /**
     * Tests that `lookupByDFS` correctly handles nested prefixes.
     */
    public function testlookupByDFSHandlesNestedPrefixes(): void
    {
        $route = new Route(
            fn() => 'callback',
            '/users/123',
            'GET'
        );

        $radixTree = [
            '/users' => [
                '/users/123' => [
                    $route->hash => $route
                ]
            ]
        ];

        $radixRouteIndex = new RadixRouteIndex($radixTree);

        // Use reflection to access the private method
        $method = $this->getPrivateMethod('lookupByDFS', $radixRouteIndex);
        $result = $method->invokeArgs($radixRouteIndex, ['/users']);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey($route->hash, $result);
        $this->assertSame($route, $result[$route->hash]);
    }

    /**
     * Tests that `lookupByDFS` skips invalid nodes gracefully.
     */
    public function testlookupByDFSSkipsInvalidNodes(): void
    {
        $route = new Route(
            fn() => 'callback',
            '/users',
            'GET'
        );

        // Add an invalid scalar value in the tree
        $radixTree = [
            '/users' => [
                $route->hash => $route,
                'invalid-node' => 'not-a-route'
            ]
        ];

        $radixRouteIndex = new RadixRouteIndex($radixTree);

        // Use reflection to access the private method
        $method = $this->getPrivateMethod('lookupByDFS', $radixRouteIndex);
        $result = $method->invokeArgs($radixRouteIndex, ['/users']);

        $this->assertCount(1, $result, 'Expected only valid routes to be collected');
        $this->assertArrayHasKey($route->hash, $result);
        $this->assertSame($route, $result[$route->hash]);
    }

    /**
     * Helper method to get a reflection of a private method.
     *
     * @param string $methodName The name of the private/protected method.
     * @param object $object The object that has the method.
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    private function getPrivateMethod(string $methodName, object $object): ReflectionMethod
    {
        $reflector = new ReflectionClass($object);
        $method = $reflector->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}