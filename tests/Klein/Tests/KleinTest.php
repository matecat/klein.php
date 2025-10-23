<?php
/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author          Chris O'Hara <cohara87@gmail.com>
 * @author          Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @author          Domenico Lupinetti (Ostico <ostico@gmail.com>) (contributor and v3 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link            https://github.com/klein/klein.php
 * @license         MIT
 */

namespace Klein\Tests;

use Exception;
use InvalidArgumentException;
use Klein\App;
use Klein\DataCollection\DataCollection;
use Klein\DataCollection\RouteCollection;
use Klein\Exceptions\DispatchHaltedException;
use Klein\Exceptions\HttpExceptionInterface;
use Klein\Exceptions\UnhandledException;
use Klein\Klein;
use Klein\Request;
use Klein\Response;
use Klein\Routes\Route;
use Klein\ServiceProvider;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Throwable;
use TypeError;

/**
 * KleinTest
 */
class KleinTest extends AbstractKleinTestCase
{
    /**
     * Constants
     */

    const TEST_CALLBACK_MESSAGE = 'yay';


    /**
     * Helpers
     */

    protected function getTestCallable($message = self::TEST_CALLBACK_MESSAGE)
    {
        return function () use ($message) {
            return $message;
        };
    }


    /**
     * Tests
     */

    public function testConstructor()
    {
        $klein = new Klein();

        $this->assertNotNull($klein);
        $this->assertTrue($klein instanceof Klein);
    }

    public function testService()
    {
        $service = $this->klein_app->service();

        $this->assertNotNull($service);
        $this->assertTrue($service instanceof ServiceProvider);
    }

    public function testApp()
    {
        $app = $this->klein_app->app();

        $this->assertNotNull($app);
        $this->assertTrue($app instanceof App);
    }

    public function testRoutes()
    {
        $routes = $this->klein_app->routes();

        $this->assertNotNull($routes);
        $this->assertTrue($routes instanceof RouteCollection);
    }

    public function testRequest()
    {
        $this->klein_app->dispatch();

        $request = $this->klein_app->request();

        $this->assertNotNull($request);
        $this->assertTrue($request instanceof Request);
    }

    public function testResponse()
    {
        $this->klein_app->dispatch();

        $response = $this->klein_app->response();

        $this->assertNotNull($response);
        $this->assertTrue($response instanceof Response);
    }

    public function testRespond()
    {
        $route = $this->klein_app->respond(callback: $this->getTestCallable());

        $object_id = spl_object_hash($route);

        $this->assertNotNull($route);
        $this->assertTrue($route instanceof Route);
        $this->assertTrue($this->klein_app->routes()->exists($object_id));
        $this->assertSame($route, $this->klein_app->routes()->get($object_id));
    }

    public function testWith()
    {
        // Test data
        $test_namespace = '/test/namespace';
        $passed_context = null;

        $this->klein_app->with(
            $test_namespace,
            function ($context) use (&$passed_context) {
                $passed_context = $context;
            }
        );

        $this->assertTrue($passed_context instanceof Klein);
    }

    public function testWithStringCallable()
    {
        // Test data
        $test_namespace = '/test/namespace';

        $this->klein_app->with(
            $test_namespace,
            'test_num_args_wrapper'
        );

        $this->expectOutputString('1');
    }

    /**
     * Weird PHPUnit bug is causing scope errors for the
     * isolated process tests, unless I run this also in an
     * isolated process
     *
     */
    #[RunInSeparateProcess]
    public function testWithUsingFileInclude()
    {
        // Test data
        $test_namespace = '/test/namespace';
        $test_routes_include = __DIR__ . '/Fixtures/routes/random.php';

        // Test file include
        $this->assertEmpty($this->klein_app->routes()->all());
        $this->klein_app->with($test_namespace, $test_routes_include);

        $this->assertNotEmpty($this->klein_app->routes()->all());

        $all_routes = array_values($this->klein_app->routes()->all());
        $test_route = $all_routes[0];

        $this->assertTrue($test_route instanceof Route);
        $this->assertSame(ltrim($test_namespace, '/') . '/?', $test_route->path);
    }

    public function testDispatch()
    {
        $request = new Request();
        $response = new Response();

        $this->klein_app->dispatch($request, $response);

        $this->assertSame($request, $this->klein_app->request());
        $this->assertSame($response, $this->klein_app->response());
    }

    public function testGetPathFor()
    {
        // Test data
        $test_path = '/test';
        $test_name = 'Test Route Thing';

        $route = new Route($this->getTestCallable(), $test_path);
        $route->setName($test_name);

        $this->klein_app->routes()->addRoute($route);

        // Make sure it fails if not prepared
        try {
            $this->klein_app->getPathFor($test_name);
        } catch (Exception $e) {
            $this->assertTrue($e instanceof OutOfBoundsException);
        }

        $this->klein_app->routes()->prepareNamed();

        $returned_path = $this->klein_app->getPathFor($test_name);

        $this->assertNotEmpty($returned_path);
        $this->assertSame($test_path, $returned_path);
    }

    public function testOnErrorWithStringCallables()
    {
        $this->klein_app->onError('test_num_args_wrapper');

        $this->klein_app->respond(
            callback: function ($request, $response, $service) {
                throw new Exception('testing');
            }
        );

        $this->assertSame(
            '4',
            $this->dispatchAndReturnOutput()
        );
    }

    public function out($a, $b, $c, $d)
    {
        echo $b;
    }

    public function testOnErrorWithBadCallables()
    {
        $this->klein_app->onError('this_function_doesnt_exist');

        $this->klein_app->respond(
            callback: function ($request, $response, $service) {
                throw new Exception('testing');
            }
        );

        $this->assertEmpty($this->klein_app->service()->flashes());

        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput()
        );

        $this->assertNotEmpty($this->klein_app->service()->flashes());

        // Clean up
        session_destroy();
    }

    public function testOnHttpError()
    {
        // Create expected arguments
        $num_of_args = 0;
        $expected_arguments = [
            'code' => null,
            'klein' => null,
            'matched' => null,
            'methods_matched' => null,
        ];

        $this->klein_app->onHttpError(
            new Route(
                function (
                    Request $request,
                    Response $response,
                    ServiceProvider $serviceProvider,
                    App $app,
                    Klein $klein,
                    DataCollection $matched,
                    array $methods_matched
                ) use (
                    &$num_of_args,
                    &$expected_arguments
                ) {
                    // Keep track of our arguments
                    $num_of_args = func_num_args();
                    $expected_arguments['code'] = $response->code();
                    $expected_arguments['klein'] = $klein;
                    $expected_arguments['matched'] = $matched;
                    $expected_arguments['methods_matched'] = $methods_matched;
                    $klein->response()->body($expected_arguments['code'] . ' error');
                }
            )
        );

        $this->klein_app->dispatch(null, null, false);

        $this->assertSame(
            '404 error',
            $this->klein_app->response()->body()
        );

        $this->assertEquals(7, $num_of_args);
        $this->assertEquals(4, count($expected_arguments));

        $this->assertTrue(is_int($expected_arguments['code']));
        $this->assertTrue($expected_arguments['klein'] instanceof Klein);
        $this->assertTrue($expected_arguments['matched'] instanceof RouteCollection);
        $this->assertTrue(is_array($expected_arguments['methods_matched']));

        $this->assertSame($expected_arguments['klein'], $this->klein_app);
    }

    public function testOnHttpErrorWithRouteDefined()
    {
        // Create expected arguments
        $num_of_args = 0;
        $expected_arguments = [
            'code' => null,
            'klein' => null,
            'matched' => null,
            'methods_matched' => null,
            'exception' => null,
        ];

        $this->klein_app->onHttpError(
            function ($code, $klein, $matched, $methods_matched, $exception) use (&$num_of_args, &$expected_arguments) {
                // Keep track of our arguments
                $num_of_args = func_num_args();
                $expected_arguments['code'] = $code;
                $expected_arguments['klein'] = $klein;
                $expected_arguments['matched'] = $matched;
                $expected_arguments['methods_matched'] = $methods_matched;
                $expected_arguments['exception'] = $exception;

                $klein->response()->body($code . ' error');
            }
        );

        $this->klein_app->dispatch(null, null, false);

        $this->assertSame(
            '404 error',
            $this->klein_app->response()->body()
        );

        $this->assertSame(count($expected_arguments), $num_of_args);

        $this->assertTrue(is_int($expected_arguments['code']));
        $this->assertTrue($expected_arguments['klein'] instanceof Klein);
        $this->assertTrue($expected_arguments['matched'] instanceof RouteCollection);
        $this->assertTrue(is_array($expected_arguments['methods_matched']));
        $this->assertTrue($expected_arguments['exception'] instanceof HttpExceptionInterface);

        $this->assertSame($expected_arguments['klein'], $this->klein_app);
    }

    public function testOnHttpErrorWithStringCallables()
    {
        $this->klein_app->onHttpError('test_num_args_wrapper');

        $this->assertSame(
            '5',
            $this->dispatchAndReturnOutput()
        );
    }

    public function testOnHttpErrorWithBadCallables()
    {
        $this->klein_app->onError('this_function_doesnt_exist');

        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput()
        );
    }

    public function testAfterDispatch()
    {
        $this->klein_app->afterDispatch(
            function ($klein) {
                $klein->response()->body('after callbacks!');
            }
        );

        $this->klein_app->dispatch(null, null, false);

        $this->assertSame(
            'after callbacks!',
            $this->klein_app->response()->body()
        );
    }

    public function testAfterDispatchWithMultipleCallbacks()
    {
        $this->klein_app->afterDispatch(
            function (Klein $klein) {
                $klein->response()->body('after callbacks!');
            }
        );

        $this->klein_app->afterDispatch(
            function ($klein) {
                $klein->response()->body('whatever');
            }
        );

        $this->klein_app->dispatch(null, null, false);

        $this->assertSame(
            'whatever',
            $this->klein_app->response()->body()
        );
    }

    public function testAfterDispatchWithStringCallables()
    {
        $this->klein_app->afterDispatch('test_response_edit_wrapper');

        $this->klein_app->dispatch(null, null, false);

        $this->assertSame(
            'after callbacks!',
            $this->klein_app->response()->body()
        );
    }

    /**
     * @throws Throwable
     */
    public function testAfterDispatchWithBadCallables()
    {
        $this->expectException(TypeError::class);
        $this->klein_app->afterDispatch('this_function_doesnt_exist');
        $this->klein_app->dispatch();
    }

    public function testAfterDispatchWithCallableThatThrowsException()
    {
        $this->expectException(UnhandledException::class);
        $this->klein_app->afterDispatch(
            function ($klein) {
                throw new Exception('testing');
            }
        );

        $this->klein_app->dispatch();

        $this->assertSame(
            500,
            $this->klein_app->response()->code()
        );
    }

    public function testErrorsWithNoCallbacks()
    {
        $this->expectException(UnhandledException::class);
        $this->klein_app->respond(
            callback: function ($request, $response, $service) {
                throw new Exception('testing');
            }
        );

        $this->klein_app->dispatch();

        $this->assertSame(
            500,
            $this->klein_app->response()->code()
        );
    }

    public function testSkipThis()
    {
        try {
            $this->klein_app->skipThis();
        } catch (Exception $e) {
            $this->assertTrue($e instanceof DispatchHaltedException);
            $this->assertSame(DispatchHaltedException::SKIP_THIS, $e->getCode());
            $this->assertSame(1, $e->getNumberOfSkips());
        }
    }

    public function testSkipNext()
    {
        $number_of_skips = 3;

        try {
            $this->klein_app->skipNext($number_of_skips);
        } catch (Exception $e) {
            $this->assertTrue($e instanceof DispatchHaltedException);
            $this->assertSame(DispatchHaltedException::SKIP_NEXT, $e->getCode());
            $this->assertSame($number_of_skips, $e->getNumberOfSkips());
        }
    }

    public function testSkipRemaining()
    {
        try {
            $this->klein_app->skipRemaining();
        } catch (Exception $e) {
            $this->assertTrue($e instanceof DispatchHaltedException);
            $this->assertSame(DispatchHaltedException::SKIP_REMAINING, $e->getCode());
        }
    }

    public function testAbort()
    {
        $test_code = 503;

        $this->klein_app->respond(
            callback: function ($a, $b, $c, $d, Klein $klein_app) use ($test_code) {
                $klein_app->abort($test_code);
            }
        );

        $this->klein_app->dispatch();

        $this->assertSame($test_code, $this->klein_app->response()->code());
        $this->assertTrue($this->klein_app->response()->isLocked());
    }

    public function testOptions()
    {
        $route = $this->klein_app->options('*', $this->getTestCallable());

        $this->assertNotNull($route);
        $this->assertSame('OPTIONS', $route->method);
    }

    public function testHead()
    {
        $route = $this->klein_app->head(callback: $this->getTestCallable());

        $this->assertNotNull($route);
        $this->assertSame('HEAD', $route->method);
    }

    public function testGet()
    {
        $route = $this->klein_app->get(callback: $this->getTestCallable());

        $this->assertNotNull($route);
        $this->assertSame('GET', $route->method);
    }

    public function testPost()
    {
        $route = $this->klein_app->post(callback: $this->getTestCallable());

        $this->assertNotNull($route);
        $this->assertSame('POST', $route->method);
    }

    public function testPut()
    {
        $route = $this->klein_app->put(callback: $this->getTestCallable());

        $this->assertNotNull($route);
        $this->assertSame('PUT', $route->method);
    }

    public function testDelete()
    {
        $route = $this->klein_app->delete(callback: $this->getTestCallable());

        $this->assertNotNull($route);
        $this->assertSame('DELETE', $route->method);
    }

    public function testPatch()
    {
        $route = $this->klein_app->patch(callback: $this->getTestCallable());

        $this->assertNotNull($route);
        $this->assertSame('PATCH', $route->method);
    }

    public function testPRespondWithNullCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->respond();
    }

    public function testPatchWithNullCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->patch();
    }

    public function testOptionsWithNullCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->options();
    }

    public function testHeadWithNullCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->head();
    }

    public function testGetWithNullCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->get();
    }

    public function testPostWithNullCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->post();
    }

    public function testPutWithNullCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->put();
    }

    public function testDeleteWithNullCallable()
    {
        // DELETE
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->delete();
    }

}
