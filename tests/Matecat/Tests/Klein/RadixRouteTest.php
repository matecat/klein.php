<?php

/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 23/10/25
 * Time: 16:17
 *
 */

namespace Matecat\Tests\Klein;

use Klein\Routes\Route;
use Klein\Routes\RouteFactory;
use Klein\Tree\RadixRouteIndex;
use Matecat\Tests\Klein;
use Matecat\Tests\Klein\Mocks\ClosureTestClass;
use Matecat\Tests\Klein\Mocks\TestGenerator;
use ReflectionClass;
use ReflectionException;

class RadixRouteTest extends Klein\AbstractKleinTestCase
{

    /**
     * @var ClosureTestClass[]
     */
    private static array $hugeTestSet;
    /**
     * @var ClosureTestClass[]
     */
    private static array $bigTestSet;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (file_exists(static::getTestFilePath('/routes/bigTestSet.serialized'))) {
            $testRoutes = unserialize(
                static::getTestFile('/routes/bigTestSet.serialized')
            );
        } else {
            $testRoutes = TestGenerator::generatePaths(1500, 8, 0);
            file_put_contents(static::getTestFilePath('/routes/bigTestSet.serialized'), serialize($testRoutes));
        }
        self::$bigTestSet = TestGenerator::generateClosureTests($testRoutes);

        if (file_exists(static::getTestFilePath('/routes/hugeTestSet.serialized'))) {
            $testRoutes = unserialize(
                static::getTestFile('/routes/hugeTestSet.serialized')
            );
        } else {
            $testRoutes = TestGenerator::generatePaths(36000, 8, 0);
            file_put_contents(static::getTestFilePath('/routes/hugeTestSet.serialized'), serialize($testRoutes));
        }
        self::$hugeTestSet = TestGenerator::generateClosureTests($testRoutes);
    }

    /**
     * @throws ReflectionException
     */
    public function testLookupByDSP(): void
    {

        $this->markTestSkippedInCoverage();

        $routeFactory = new RouteFactory();
        $radixTree = new RadixRouteIndex();

        foreach (self::$bigTestSet as $test) {
            $radixTree->addRoute($routeFactory->build($test->closure, $test->registerPath, 'GET'));
        }

        $reflectionClass = new ReflectionClass($radixTree);
        $findLookupReflector = $reflectionClass->getMethod('findPossibleRoutes');

        $radixTreeReflector = $reflectionClass->getProperty('radixTree');

        $radixTreeArray = $radixTreeReflector->getValue($radixTree);

        $this->assertTrue(count($radixTreeArray) < 130000);

        $pickRandomTest = self::$bigTestSet[array_rand(self::$bigTestSet)];

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
    public function testLookupByArrayWalk(): void
    {

        $this->markTestSkippedInCoverage();

        $routeFactory = new RouteFactory();
        $radixTree = new RadixRouteIndex();

        foreach (self::$hugeTestSet as $test) {
            $radixTree->addRoute($routeFactory->build($test->closure, $test->registerPath, 'GET'));
        }

        $reflectionClass = new ReflectionClass($radixTree);
        $findLookupReflector = $reflectionClass->getMethod('findPossibleRoutes');

        $radixTreeReflector = $reflectionClass->getProperty('radixTree');

        $radixTreeArray = $radixTreeReflector->getValue($radixTree);

        $this->assertTrue(count($radixTreeArray) > 130000);

        $pickRandomTest = self::$hugeTestSet[array_rand(self::$hugeTestSet)];

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