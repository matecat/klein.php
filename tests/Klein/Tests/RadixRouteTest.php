<?php
/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 23/10/25
 * Time: 16:17
 *
 */

namespace Klein\Tests;

use Klein\Routes\Route;
use Klein\Routes\RouteFactory;
use Klein\Tests\Fixtures\TestGenerator;
use Klein\Tree\RadixRouteIndex;
use ReflectionClass;
use ReflectionException;

class RadixRouteTest extends AbstractKleinTestCase
{

    /**
     * @var Fixtures\ClosureTestClass[]
     */
    private static array $hugeTestSet;
    /**
     * @var Fixtures\ClosureTestClass[]
     */
    private static array $bigTestSet;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (file_exists(__DIR__ . '/Fixtures/routes/bigTestSet.serialized')) {
            $testRoutes = unserialize(
                file_get_contents(__DIR__ . '/Fixtures/routes/bigTestSet.serialized')
            );
        } else {
            $testRoutes = TestGenerator::generatePaths(1500, 8, 0);
            file_put_contents(__DIR__ . '/Fixtures/routes/bigTestSet.serialized', serialize($testRoutes));
        }
        static::$bigTestSet = TestGenerator::generateClosureTests($testRoutes);

        if (file_exists(__DIR__ . '/Fixtures/routes/hugeTestSet.serialized')) {
            $testRoutes = unserialize(
                file_get_contents(__DIR__ . '/Fixtures/routes/hugeTestSet.serialized')
            );
        } else {
            $testRoutes = TestGenerator::generatePaths(36000, 8, 0);
            file_put_contents(__DIR__ . '/Fixtures/routes/hugeTestSet.serialized', serialize($testRoutes));
        }
        static::$hugeTestSet = TestGenerator::generateClosureTests($testRoutes);
    }

    /**
     * @throws ReflectionException
     */
    public function testLookupByDSP()
    {
        $routeFactory = new RouteFactory();
        $radixTree = new RadixRouteIndex();

        foreach (static::$bigTestSet as $test) {
            $radixTree->addRoute($routeFactory->build($test->closure, $test->registerPath, 'GET'));
        }

        $reflectionClass = new ReflectionClass($radixTree);
        $findLookupReflector = $reflectionClass->getMethod('findPossibleRoutes');
        $findLookupReflector->setAccessible(true);

        $radixTreeReflector = $reflectionClass->getProperty('radixTree');
        $radixTreeReflector->setAccessible(true);

        $radixTreeArray = $radixTreeReflector->getValue($radixTree);

        $this->assertTrue(count($radixTreeArray) < 130000);

        $pickRandomTest = static::$bigTestSet[array_rand(static::$bigTestSet)];

        // Use reflection to access the private method
        /** @var array<string,Route> $resultList */
        $resultList = $findLookupReflector->invoke($radixTree, $pickRandomTest->path);
        $this->assertNotEmpty($resultList);

        $found = false;
        foreach ($resultList as $result) {
            if (preg_match($result->getCompiledRegex(), $pickRandomTest->path) === 1) {
                $found = true;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * @throws ReflectionException
     */
    public function testLookupByArrayWalk()
    {
        $routeFactory = new RouteFactory();
        $radixTree = new RadixRouteIndex();

        foreach (static::$hugeTestSet as $test) {
            $radixTree->addRoute($routeFactory->build($test->closure, $test->registerPath, 'GET'));
        }

        $reflectionClass = new ReflectionClass($radixTree);
        $findLookupReflector = $reflectionClass->getMethod('findPossibleRoutes');
        $findLookupReflector->setAccessible(true);

        $radixTreeReflector = $reflectionClass->getProperty('radixTree');
        $radixTreeReflector->setAccessible(true);

        $radixTreeArray = $radixTreeReflector->getValue($radixTree);

        $this->assertTrue(count($radixTreeArray) > 130000);

        $pickRandomTest = static::$hugeTestSet[array_rand(static::$hugeTestSet)];

        // Use reflection to access the private method
        /** @var array<string,Route> $resultList */
        $resultList = $findLookupReflector->invoke($radixTree, $pickRandomTest->path);
        $this->assertNotEmpty($resultList);

        $found = false;
        foreach ($resultList as $result) {
            if (preg_match($result->getCompiledRegex(), $pickRandomTest->path) === 1) {
                $found = true;
            }
        }

        $this->assertTrue($found);
    }

}