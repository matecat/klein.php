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

namespace Klein\Tests;

use Closure;
use InvalidArgumentException;
use Klein\Routes\Route;
use TypeError;

/**
 * RouteTest
 */
class RouteTest extends AbstractKleinTestCase
{

    protected function getTestCallable(): Closure
    {
        return function () {
            echo 'dog';
        };
    }

    public function testCallbackGetSet()
    {
        // Test functions
        $test_callable = $this->getTestCallable();
        $test_class_callable = __NAMESPACE__ . '\Mocks\TestClass::GET';

        // Callback set in constructor
        $route = new Route($test_callable);

        $this->assertSame($test_callable, $route->callback);
        $this->assertIsCallable($route->callback);

        // Callback set in method
        $route = new Route($test_class_callable);

        $this->assertSame($test_class_callable, $route->callback);
        $this->assertIsCallable($route->callback);
    }

    public function testPathGetSet()
    {
        // Test data
        $test_callable = $this->getTestCallable();
        $test_path = '/this-is-a-path';

        // Empty constructor
        $route = new Route($test_callable);

        $this->assertNotNull($route->path);
        $this->assertIsString($route->path);

        // Set in constructor
        $route = new Route($test_callable, $test_path);

        $this->assertSame($test_path, $route->path);
    }

    public function testMethodGetSet()
    {
        // Test data
        $test_callable = $this->getTestCallable();
        $test_method_string = 'POST';
        $test_method_array = array('POST', 'PATCH');

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

    public function testCountMatchGetSet()
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

    public function testNameGetSet()
    {
        // Test data
        $test_callable = $this->getTestCallable();
        $test_name = 'trevor';

        // Empty constructor
        $route = new Route($test_callable);

        $this->assertNull($route->getName());

        // Set in constructor
        $route = new Route($test_callable, '', null, '', true, null, $test_name);

        $this->assertSame($test_name, $route->getName());

        // Set in method
        $route = new Route($test_callable);
        $route->setName($test_name);

        $this->assertSame($test_name, $route->getName());
    }

    public function testInvokeMethod()
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
     */

    public function testCallbackSetWithIncorrectType()
    {
        $this->expectException(TypeError::class);
        // Test setting with the WRONG type
        new Route(100);
    }

    public function testMethodSetWithIncorrectType()
    {
        $this->expectException(InvalidArgumentException::class);
        // Test setting with the WRONG type
        new Route($this->getTestCallable(), "", 100);
    }
}
