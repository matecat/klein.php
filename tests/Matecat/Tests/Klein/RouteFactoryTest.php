<?php
/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/klein/klein.php
 * @license     MIT
 */

namespace Matecat\Tests\Klein;

use Klein\Routes\Route;
use Klein\Routes\RouteFactory;
use Matecat\Tests\Klein;
use InvalidArgumentException;

/**
 * RouteFactoryTest
 */
class RouteFactoryTest extends Klein\AbstractKleinTestCase
{

    /**
     * Constants
     */

    const TEST_CALLBACK_MESSAGE = 'yay';


    /**
     * Helpers
     */

    protected function getTestCallable(string $message = self::TEST_CALLBACK_MESSAGE): \Closure
    {
        return function () use ($message) {
            return $message;
        };
    }


    /**
     * Tests
     * @throws InvalidArgumentException
     */

    public function testBuildBasic(
        ?string $test_namespace = null,
        ?string $test_path = null,
        bool $test_paths_match = true,
        bool $should_match = true
    ): void {
        // Test data
        $test_path = is_string($test_path) ? $test_path : '/test';
        $test_callable = $this->getTestCallable();


        $factory = new RouteFactory($test_namespace);

        $route = $factory->build(
            $test_callable,
            $test_path
        );

        $this->assertInstanceOf(Route::class, $route);
        $this->assertNull($route->method);
        $this->assertNull($route->getName());
        $this->assertSame($test_callable(), $route());

        $this->assertSame($should_match, $route->countMatch);

        if ($test_paths_match) {
            $this->assertStringContainsString($route->path, $test_path);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBuildWithNamespacedPath(): void
    {
        // Test data
        $test_namespace = '/users';
        $test_path = '/test';

        $this->testBuildBasic($test_namespace, $test_path, false);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBuildWithNamespacedCatchAllPath(): void
    {
        // Test data
        $test_namespace = '/users';
        $test_path = '*';

        $this->testBuildBasic($test_namespace, $test_path, false, false);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBuildWithNamespacedNullPath(): void
    {
        // Test data
        $test_namespace = '/users';

        $this->testBuildBasic($test_namespace, null, false);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBuildWithNamespacedEmptyPath(): void
    {
        // Test data
        $test_namespace = '/users';
        $test_path = '';

        $this->testBuildBasic($test_namespace, $test_path, false, true);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBuildWithCustomRegexPath(): void
    {
        // Test data
        $test_path = '@/test';

        $this->testBuildBasic(null, $test_path);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBuildWithCustomRegexNamespacedPath(): void
    {
        // Test data
        $test_namespace = '/users';
        $test_path = '@/test';

        $this->testBuildBasic($test_namespace, $test_path, false);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBuildWithCustomNegatedRegexPath(): void
    {
        // Test data
        $test_path = '!@/test';

        $this->testBuildBasic(null, $test_path, false);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBuildWithCustomNegatedAnchoredRegexPath(): void
    {
        // Test data
        $test_path = '!@^/test';

        $this->testBuildBasic(null, $test_path, false);
    }
}
