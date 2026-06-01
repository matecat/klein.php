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

use Closure;
use InvalidArgumentException;
use Klein\Routes\Route;
use Matecat\Tests\Klein\Mocks\TestClass;
use TypeError;

/**
 * RouteTest
 */
class RouteTest extends AbstractKleinTestCase
{

    /**
     * Returns a value of the given type, opaque to static analysis.
     */
    private function value(mixed $val): mixed
    {
        return $val;
    }

    protected function getTestCallable(): Closure
    {
        return function () {
            echo 'dog';
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCallbackGetSet(): void
    {
        // Test functions
        $test_callable = $this->getTestCallable();
        $test_class_callable = [TestClass::class, 'get'];

        // Callback set in constructor
        $route = new Route($test_callable);

        $this->assertSame($test_callable, $route->callback);
        $this->assertIsCallable($route->callback);

        // Callback set in method
        $route = new Route($test_class_callable);

        $this->assertSame($test_class_callable, $route->callback);
        $this->assertIsCallable($route->callback);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testPathGetSet(): void
    {
        // Test data
        $test_callable = $this->getTestCallable();
        $test_path = '/this-is-a-path';

        // Empty constructor
        $route = new Route($test_callable);

        $this->assertNotEmpty($route->path);

        // Set in constructor
        $route = new Route($test_callable, $test_path);

        $this->assertSame(ltrim($test_path, '/'), $route->path);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testMethodGetSet(): void
    {
        // Test data
        $test_callable = $this->getTestCallable();
        $test_method_string = 'POST';
        $test_method_array = ['POST', 'PATCH'];

        // Empty constructor
        $route = new Route($test_callable);

        $this->assertNull($route->method);

        // Set in constructor
        $route = new Route($test_callable, '', $test_method_string);

        $this->assertSame($test_method_string, $route->method);

        // Set in method
        $route = new Route($test_callable, '', $test_method_array);

        $this->assertSame($test_method_array, $route->method);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCountMatchGetSet(): void
    {
        // Test data
        $test_callable = $this->getTestCallable();

        // Empty constructor
        $route = new Route($test_callable);

        $this->assertTrue($route->countMatch);

        // Set in constructor
        $route = new Route($test_callable, '', null, null, false);

        $this->assertFalse($route->countMatch);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testNameGetSet(): void
    {
        // Test data
        $test_callable = $this->getTestCallable();
        $test_name = 'trevor';

        // Empty constructor
        $route = new Route($test_callable);

        $this->assertNull($route->getName());

        // Set in constructor
        $route = new Route($test_callable, '', null, '', true, $test_name);

        $this->assertSame($test_name, $route->getName());

        // Set in method
        $route = new Route($test_callable);
        $route->setName($test_name);

        $this->assertSame($test_name, $route->getName());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testInvokeMethod(): void
    {
        // Test data
        $test_callable = function ($id, $name) {
            return [$id, $name];
        };
        $test_arguments = [7, 'Trevor'];

        $route = new Route($test_callable);

        $this->assertSame(
            call_user_func_array($test_callable, $test_arguments),
            call_user_func_array($route, $test_arguments)
        );
    }

    /**
     * Exception tests
     * @throws InvalidArgumentException
     */

    public function testCallbackSetWithIncorrectType(): void
    {
        $this->expectException(TypeError::class);
        // Test setting with the WRONG type
        new Route($this->value(100));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testMethodSetWithIncorrectType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Test setting with the WRONG type
        new Route($this->getTestCallable(), "", $this->value(100));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testIncorrectMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Route($this->getTestCallable(), "", "GETT");
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testIncorrectMethodArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Route($this->getTestCallable(), "", ["GETT", "POST"]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testIncorrectMethodArrayNumbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Route($this->getTestCallable(), "", ["123"]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testIncorrectMethodWithNestedArrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Route($this->getTestCallable(), "", $this->value(["GET", ["HEAD", "POST"]]));
    }
}
