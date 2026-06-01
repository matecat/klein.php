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

namespace Matecat\Tests\Klein;

use Closure;
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
use Klein\Exceptions\LockedResponseException;
use RuntimeException;

/**
 * KleinTest
 */
class KleinTest extends \Matecat\Tests\Klein\AbstractKleinTestCase
{
    /**
     * Constants
     */

    const TEST_CALLBACK_MESSAGE = 'yay';


    /**
     * Helpers
     */

    protected function getTestCallable(string $message = self::TEST_CALLBACK_MESSAGE): Closure
    {
        return function () use ($message) {
            return $message;
        };
    }


    /**
     * Tests
     * @throws InvalidArgumentException
     */

    public function testConstructor(): void
    {
        $klein = new Klein();

        $this->assertInstanceOf(Klein::class, $klein);
    }

    public function testService(): void
    {
        $service = $this->klein_app->service();

        $this->assertInstanceOf(ServiceProvider::class, $service);
    }

    public function testApp(): void
    {
        $app = $this->klein_app->app();

        $this->assertInstanceOf(App::class, $app);
    }

    public function testRoutes(): void
    {
        $routes = $this->klein_app->routes();

        $this->assertInstanceOf(RouteCollection::class, $routes);
    }

    public function testRequest(): void
    {
        $this->klein_app->dispatch();

        $request = $this->klein_app->request();

        $this->assertInstanceOf(Request::class, $request);
    }

    public function testResponse(): void
    {
        $this->klein_app->dispatch();

        $response = $this->klein_app->response();

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testRespond(): void
    {
        $route = $this->klein_app->respond(callback: $this->getTestCallable());

        $object_id = spl_object_hash($route);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertTrue($this->klein_app->routes()->exists($object_id));
        $this->assertSame($route, $this->klein_app->routes()->get($object_id));
    }

    public function testWith(): void
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

        $this->assertInstanceOf(Klein::class, $passed_context);
    }

    public function testWithStringCallable(): void
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
    public function testWithUsingFileInclude(): void
    {
        // Test data
        $test_namespace = '/test/namespace';
        $test_routes_include = static::getTestFilePath('/routes/random.php');

        // Test file include
        $this->assertEmpty($this->klein_app->routes()->all());
        $this->klein_app->with($test_namespace, $test_routes_include);

        $this->assertNotEmpty($this->klein_app->routes()->all());

        $all_routes = array_values($this->klein_app->routes()->all());
        $test_route = $all_routes[0];

        $this->assertInstanceOf(Route::class, $test_route);
        $this->assertSame(ltrim($test_namespace, '/') . '/?', $test_route->path);
    }

    /**
     * @throws LockedResponseException
     */
    public function testDispatch(): void
    {
        $request = new Request();
        $response = new Response();

        $this->klein_app->dispatch($request, $response);

        $this->assertSame($request, $this->klein_app->request());
        $this->assertSame($response, $this->klein_app->response());
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function testGetPathFor(): void
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
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }

        $this->klein_app->routes()->prepareNamed();

        $returned_path = $this->klein_app->getPathFor($test_name);

        $this->assertNotEmpty($returned_path);
        $this->assertSame($test_path, $returned_path);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testOnErrorWithStringCallables(): void
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

    public function out(mixed $a, mixed $b, mixed $c, mixed $d): void
    {
        echo $b;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testOnErrorWithBadCallables(): void
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

    /**
     * @throws InvalidArgumentException
     * @throws LockedResponseException
     */
    public function testOnHttpError(): void
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
                    $klein->response()->body($expected_arguments['code'] . ' error'); // @phpstan-ignore binaryOp.invalid
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
        $this->assertInstanceOf(Klein::class, $expected_arguments['klein']);
        $this->assertInstanceOf(RouteCollection::class, $expected_arguments['matched']);
        $this->assertTrue(is_array($expected_arguments['methods_matched']));

        $this->assertSame($expected_arguments['klein'], $this->klein_app);
    }

    /**
     * @throws LockedResponseException
     */
    public function testOnHttpErrorWithRouteDefined(): void
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
        $this->assertInstanceOf(Klein::class, $expected_arguments['klein']);
        $this->assertInstanceOf(RouteCollection::class, $expected_arguments['matched']);
        $this->assertTrue(is_array($expected_arguments['methods_matched']));
        $this->assertInstanceOf(HttpExceptionInterface::class, $expected_arguments['exception']);

        $this->assertSame($expected_arguments['klein'], $this->klein_app);
    }

    public function testOnHttpErrorWithStringCallables(): void
    {
        $this->klein_app->onHttpError('test_num_args_wrapper');

        $this->assertSame(
            '5',
            $this->dispatchAndReturnOutput()
        );
    }

    public function testOnHttpErrorWithBadCallables(): void
    {
        $this->klein_app->onError('this_function_doesnt_exist');

        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput()
        );
    }

    /**
     * @throws LockedResponseException
     */
    public function testAfterDispatch(): void
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

    /**
     * @throws LockedResponseException
     */
    public function testAfterDispatchWithMultipleCallbacks(): void
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

    /**
     * @throws LockedResponseException
     */
    public function testAfterDispatchWithStringCallables(): void
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
    public function testAfterDispatchWithBadCallables(): void
    {
        $this->expectException(TypeError::class);
        $this->klein_app->afterDispatch('this_function_doesnt_exist'); // @phpstan-ignore argument.type
        $this->klein_app->dispatch();
    }

    /**
     * @throws LockedResponseException
     */
    public function testAfterDispatchWithCallableThatThrowsException(): void
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

    /**
     * @throws InvalidArgumentException
     * @throws LockedResponseException
     */
    public function testErrorsWithNoCallbacks(): void
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

    public function testSkipThis(): void
    {
        try {
            $this->klein_app->skipThis();
        } catch (Exception $e) {
            $this->assertInstanceOf(DispatchHaltedException::class, $e);
            $this->assertSame(DispatchHaltedException::SKIP_THIS, $e->getCode());
            $this->assertSame(1, $e->getNumberOfSkips());
        }
    }

    public function testSkipNext(): void
    {
        $number_of_skips = 3;

        try {
            $this->klein_app->skipNext($number_of_skips);
        } catch (Exception $e) {
            $this->assertInstanceOf(DispatchHaltedException::class, $e);
            $this->assertSame(DispatchHaltedException::SKIP_NEXT, $e->getCode());
            $this->assertSame($number_of_skips, $e->getNumberOfSkips());
        }
    }

    public function testSkipRemaining(): void
    {
        try {
            $this->klein_app->skipRemaining();
        } catch (Exception $e) {
            $this->assertInstanceOf(DispatchHaltedException::class, $e);
            $this->assertSame(DispatchHaltedException::SKIP_REMAINING, $e->getCode());
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws LockedResponseException
     */
    public function testAbort(): void
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

    /**
     * @throws InvalidArgumentException
     */
    public function testOptions(): void
    {
        $route = $this->klein_app->options('*', $this->getTestCallable());

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('OPTIONS', $route->method);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testHead(): void
    {
        $route = $this->klein_app->head(callback: $this->getTestCallable());

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('HEAD', $route->method);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGet(): void
    {
        $route = $this->klein_app->get(callback: $this->getTestCallable());

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('GET', $route->method);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testPost(): void
    {
        $route = $this->klein_app->post(callback: $this->getTestCallable());

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('POST', $route->method);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testPut(): void
    {
        $route = $this->klein_app->put(callback: $this->getTestCallable());

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('PUT', $route->method);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testDelete(): void
    {
        $route = $this->klein_app->delete(callback: $this->getTestCallable());

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('DELETE', $route->method);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testPatch(): void
    {
        $route = $this->klein_app->patch(callback: $this->getTestCallable());

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('PATCH', $route->method);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testPRespondWithNullCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->respond();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testPatchWithNullCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->patch();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testOptionsWithNullCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->options();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testHeadWithNullCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->head();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetWithNullCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->get();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testPostWithNullCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->post();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testPutWithNullCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->put();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testDeleteWithNullCallable(): void
    {
        // DELETE
        $this->expectException(InvalidArgumentException::class);
        $this->klein_app->delete();
    }

}
