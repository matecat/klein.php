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

use Klein\DataCollection\DataCollection;
use Klein\Exceptions\ValidationException;
use Klein\Request;
use Klein\Response;
use Klein\ServiceProvider;
use Klein\Validator;
use Matecat\Tests\Klein;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionException;
use ReflectionProperty;
use Klein\Exceptions\LockedResponseException;
use InvalidArgumentException;

/**
 * ServiceProviderTest
 */
class ServiceProviderTest extends Klein\AbstractKleinTestCase
{

    /**
     * @throws LockedResponseException
     */
    protected function getBasicServiceProvider(): ServiceProvider
    {
        return new ServiceProvider(
            $request = new Request(),
            $response = new Response()
        );
    }

    /**
     * @throws ReflectionException
     * @throws LockedResponseException
     */
    public function testConstructor(): void
    {
        $service = new ServiceProvider();

        $requestReflection = new ReflectionProperty($service, 'request');
        $responseReflection = new ReflectionProperty($service, 'response');

        // Make sure our attributes are first null
        $this->assertNull($requestReflection->getValue($service));
        $this->assertNull($responseReflection->getValue($service));

        // New service with injected dependencies
        $service = new ServiceProvider(
            $request = new Request(),
            $response = new Response()
        );

        // Make sure our attributes are set
        $this->assertEquals($request, $requestReflection->getValue($service));
        $this->assertEquals($response, $responseReflection->getValue($service));
    }

    /**
     * @throws ReflectionException
     * @throws LockedResponseException
     */
    public function testBinder(): void
    {
        $service = new ServiceProvider();

        $requestReflection = new ReflectionProperty($service, 'request');
        $responseReflection = new ReflectionProperty($service, 'response');

        // Make sure our attributes are first null
        $this->assertNull($requestReflection->getValue($service));
        $this->assertNull($responseReflection->getValue($service));

        // New service with injected dependencies
        $return_val = $service->bind(
            $request = new Request(),
            $response = new Response()
        );

        // Make sure our attributes are set
        $this->assertEquals($request, $requestReflection->getValue($service));
        $this->assertEquals($response, $responseReflection->getValue($service));

        // Make sure we're chainable
        $this->assertEquals($service, $return_val);
        $this->assertSame($service, $return_val);
    }

    public function testSharedDataGetter(): void
    {
        $service = new ServiceProvider();

        $this->assertInstanceOf(DataCollection::class, $service->sharedData());
    }

    public function testStartSession(): void
    {
        $service = new ServiceProvider();

        $returned = $service->startSession();

        $this->assertSame(session_id(), $returned);

        // Clean up
        session_destroy();
    }

    public function testStartSessionFails(): void
    {
        // Only care about some errors, and keep the old value
        $old_error_val = error_reporting();
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

        session_id('');
        session_start();

        $service = new ServiceProvider();

        $returned = $service->startSession();

        $this->assertFalse($returned);

        // Clean up
        session_destroy();
        error_reporting($old_error_val);
    }

    public function testFlash(): void
    {
        // Test data
        $test_session_key = '__flashes';
        $test_flashes = [
            [
                'message' => 'Test info message',
                'type' => 'info',
            ],
            [
                'message' => 'Test error message',
                'type' => 'error',
            ],
        ];

        $service = new ServiceProvider();

        $this->assertEmpty($_SESSION);

        $service->flash($test_flashes[0]['message'], $test_flashes[0]['type']);
        $service->flash($test_flashes[1]['message'], $test_flashes[1]['type']);

        $this->assertNotEmpty($_SESSION);
        $this->assertSame($test_flashes[0]['message'], $_SESSION[$test_session_key][$test_flashes[0]['type']][0]);
        $this->assertSame($test_flashes[1]['message'], $_SESSION[$test_session_key][$test_flashes[1]['type']][0]);

        // Clean up
        session_destroy();
        $_SESSION = [];
    }

    public function testFlashWithMarkdown(): void
    {
        // Test data
        $test_session_key = '__flashes';
        $test_type = 'info';
        $test_message = 'Test message by %s %s';
        $test_params = [
            'Trevor',
            'Suarez',
        ];
        $test_processed = 'Test message by ' . $test_params[0] . ' ' . $test_params[1];

        $service = new ServiceProvider();

        $this->assertEmpty($_SESSION);

        $service->flashInfo($test_message, $test_params);

        $this->assertNotEmpty($_SESSION);
        $this->assertSame($test_processed, $_SESSION[$test_session_key][$test_type][0]);

        // Clean up
        session_destroy();
        $_SESSION = [];
    }

    public function testFlashes(): void
    {
        // Test data
        $test_session_key = '__flashes';
        $test_flashes = [
            [
                'message' => 'Test info message',
                'type' => 'info',
            ],
            [
                'message' => 'Test error message',
                'type' => 'error',
            ],
            [
                'message' => 'Test second error message',
                'type' => 'error',
            ],
            [
                'message' => 'Test whatever message',
                'type' => 'whatever',
            ],
        ];
        $test_error_flashes = [
            $test_flashes[1]['message'],
            $test_flashes[2]['message'],
        ];

        $service = new ServiceProvider();

        $this->assertEmpty($_SESSION);
        $this->assertEmpty($service->flashes());

        $service->flash($test_flashes[0]['message'], $test_flashes[0]['type']);
        $service->flash($test_flashes[1]['message'], $test_flashes[1]['type']);
        $service->flash($test_flashes[2]['message'], $test_flashes[2]['type']);
        $service->flash($test_flashes[3]['message'], $test_flashes[3]['type']);

        // Test error flashes only
        $error_flashes = $service->flashes('error');
        $this->assertCount(2, $error_flashes);
        $this->assertSame($test_error_flashes, $error_flashes);

        // Test the rest
        $all_flashes = $service->flashes();
        $this->assertCount(
            count($test_flashes) - count($error_flashes),
            $all_flashes
        );

        // Clean up
        session_destroy();
        $_SESSION = [];
    }

    public function testMarkdownParser(): void
    {
        // Test basic markdown conversion
        $this->assertSame(
            '<strong>dog</strong> <em>cat</em> <a href="src" rel="noopener noreferrer">name</a>',
            ServiceProvider::markdown('**dog** *cat* [name](src)')
        );

        // Test array arguments
        $this->assertSame(
            '<strong>huh</strong> <em>12</em> <strong>CD</strong>',
            ServiceProvider::markdown('**%s** *%d* **%X**', ['huh', '12', 205])
        );

        // Test second array argument overrides other arguments
        $this->assertSame(
            '<strong>huh</strong> <em>12</em> <strong>CD</strong>',
            ServiceProvider::markdown('**%s** *%d* **%X**', ['huh', '12', 205])
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testMarkdownXssPrevention(): void
    {
        // Raw HTML injection — script tags must be escaped
        $this->assertStringNotContainsString(
            '<script>',
            ServiceProvider::markdown('<script>alert(1)</script>')
        );

        // javascript: protocol in link URL must be neutralized
        $result = ServiceProvider::markdown('[click](javascript:alert(1))');
        $this->assertStringNotContainsString('javascript:', $result);

        // data: protocol in link URL must be neutralized
        $result = ServiceProvider::markdown('[click](data:text/html,<script>alert(1)</script>)');
        $this->assertStringNotContainsString('data:', $result);

        // Event handler injection via link text — img tag must be escaped
        $result = ServiceProvider::markdown('[<img src=x onerror=alert(1)>](http://safe.com)');
        $this->assertStringNotContainsString('<img', $result);

        // Attribute breakout in URL via double quote
        $result = ServiceProvider::markdown('[x](http://x" onclick="alert(1))');
        $this->assertStringNotContainsString('onclick', $result);

        // Ensure normal markdown still works after hardening
        $this->assertSame(
            '<strong>bold</strong> <em>italic</em>',
            ServiceProvider::markdown('**bold** *italic*')
        );

        // Ensure safe links still work (using blocklist — bare relative URLs allowed)
        $this->assertSame(
            '<a href="https://example.com" rel="noopener noreferrer">link</a>',
            ServiceProvider::markdown('[link](https://example.com)')
        );

        // Ensure mailto links work
        $this->assertSame(
            '<a href="mailto:test@example.com" rel="noopener noreferrer">email</a>',
            ServiceProvider::markdown('[email](mailto:test@example.com)')
        );
    }

    public function testEscapeCharacters(): void
    {
        $this->assertSame(
            'H&egrave;&egrave;&egrave;llo! A&amp;W root beer is now 20% off!!',
            ServiceProvider::escape('Hèèèllo! A&W root beer is now 20% off!!')
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws LockedResponseException
     */
    public function testRefresh(): void
    {
        $this->klein_app->respond(
            callback: function ($request, $response, $service) {
                $service->refresh();
            }
        );

        $this->klein_app->dispatch();

        $this->assertSame(
            $this->klein_app->request()->uri(),
            $this->klein_app->response()->headers()->get('Location')
        );
        $this->assertTrue($this->klein_app->response()->isLocked());

        // Make sure we got a 3xx response code
        $this->assertGreaterThan(299, $this->klein_app->response()->code());
        $this->assertLessThan(400, $this->klein_app->response()->code());
    }

    /**
     * @throws InvalidArgumentException
     * @throws LockedResponseException
     */
    public function testBack(): void
    {
        $url = 'http://google.com/';

        $request = new Request();
        $request->server()->set('HTTP_REFERER', $url);

        $this->klein_app->respond(
            callback: function ($request, $response, ServiceProvider $service) {
                $service->back();
            }
        );

        $this->klein_app->dispatch($request);

        $this->assertSame(
            $url,
            $this->klein_app->response()->headers()->get('Location')
        );
        $this->assertTrue($this->klein_app->response()->isLocked());

        // Make sure we got a 3xx response code
        $this->assertGreaterThan(299, $this->klein_app->response()->code());
        $this->assertLessThan(400, $this->klein_app->response()->code());
    }

    /**
     * @throws InvalidArgumentException
     * @throws LockedResponseException
     */
    public function testBackWithoutRefererSet(): void
    {
        $request = new Request();

        $this->klein_app->respond(
            callback: function ($request, $response, $service) {
                $service->back();
            }
        );

        $this->klein_app->dispatch($request);

        $this->assertTrue($this->klein_app->response()->isLocked());

        // Make sure we got a 3xx response code
        $this->assertGreaterThan(299, $this->klein_app->response()->code());
        $this->assertLessThan(400, $this->klein_app->response()->code());
    }

    public function testLayoutGetSet(): void
    {
        $test_layout = 'boom!! :D';

        $service = new ServiceProvider();

        $this->assertEmpty($service->layout());

        $service->layout($test_layout);

        $this->assertSame($test_layout, $service->layout());
    }

    /**
     * NOTE: Also tests "yield()"
     * @throws InvalidArgumentException
     */
    public function testRender(): void
    {
        $test_data = [
            'name' => 'trevor suarez',
            'title' => 'about',
            'verb' => 'woot',
        ];

        $this->klein_app->respond(
            callback: function ($request, $response, $service) use ($test_data) {
                // Set some data manually
                $service->sharedData()->set('name', 'should be overwritten');

                // Set our layout
                $service->layout(static::getTestFilePath('views/layout.php'));

                // Render our view, and pass some MORE data
                $service->render(
                    static::getTestFilePath('views/test.php'),
                    $test_data
                );
            }
        );

        $this->klein_app->dispatch();

        $this->expectOutputString(
            '<h1>About</h1>' . PHP_EOL
            . 'My name is Trevor Suarez.' . PHP_EOL
            . 'WOOT!' . PHP_EOL
            . '<div>footer</div>' . PHP_EOL
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws LockedResponseException
     */
    public function testRenderChunked(): void
    {
        $test_data = [
            'name' => 'trevor suarez',
            'title' => 'about',
            'verb' => 'woot',
        ];

        $response = new Response();
        $response->chunk();

        $this->klein_app->respond(
            callback: function ($request, $response, $service) use ($test_data) {
                // Set some data manually
                $service->sharedData()->set('name', 'should be overwritten');

                // Set our layout
                $service->layout(static::getTestFilePath('views/layout.php'));

                // Render our view, and pass some MORE data
                $service->render(
                    static::getTestFilePath('views/test.php'),
                    $test_data
                );
            }
        );

        $this->klein_app->dispatch(null, $response);

        $this->expectOutputString(
            '<h1>About</h1>' . PHP_EOL
            . 'My name is Trevor Suarez.' . PHP_EOL
            . 'WOOT!' . PHP_EOL
            . '<div>footer</div>' . PHP_EOL
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testPartial(): void
    {
        $test_data = [
            'name' => 'trevor suarez',
            'title' => 'about',
            'verb' => 'woot',
        ];

        $this->klein_app->respond(
            callback: function ($request, $response, $service) use ($test_data) {
                // Set our layout
                $service->layout(static::getTestFilePath('views/layout.php'));

                // Render our view, and pass some MORE data
                $service->partial(
                    static::getTestFilePath('views/test.php'),
                    $test_data
                );
            }
        );

        $this->klein_app->dispatch();

        // Make sure the layout doesn't get included
        $this->expectOutputString(
            'My name is Trevor Suarez.' . PHP_EOL
            . 'WOOT!' . PHP_EOL
        );
    }

    /**
     * @return void
     */
    #[RunInSeparateProcess]
    public function testAddValidator(): void
    {
        $service = new ServiceProvider();

        // Initially empty
        $this->assertEmpty(Validator::$methods);

        $test_callback = function () {
            echo 'test';
        };

        $service->addValidator('awesome', $test_callback);

        $this->assertNotEmpty(Validator::$methods);
        $this->assertArrayHasKey('awesome', Validator::$methods);
        $this->assertContains($test_callback, Validator::$methods);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testValidate(): void
    {
        $this->expectException(ValidationException::class);
        $this->klein_app->onError(
            function ($a, $b, $c, $exception) {
                throw $exception;
            }
        );

        $this->klein_app->respond(
            callback: function ($request, $response, ServiceProvider $service) {
                $service->validate('thing')->isLen(3);
            }
        );

        $this->klein_app->dispatch();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testValidateParam(): void
    {
        $this->expectException(ValidationException::class);
        $this->klein_app->onError(
            function ($a, $b, $c, $exception) {
                throw $exception;
            }
        );

        $this->klein_app->respond(
            callback: function ($request, $response, $service) {
                // Set a test param
                $request->paramsNamed()->set('name', 'trevor');

                $service->validateParam('name')->notNull()->isLen(3);
            }
        );

        $this->klein_app->dispatch();
    }

    // Test ALL of the magic setter, getter, exists, and removal methods
    public function testMagicGetSetExistsRemove(): void
    {
        $test_data = [
            'name' => 'huh?',
        ];

        $service = new ServiceProvider();

        $this->assertEmpty($service->sharedData()->all());
        $this->assertNull($service->sharedData()->get('test_data'));
        $this->assertNull($service->name);
        $this->assertFalse(isset($service->name));

        $service->name = $test_data['name'];

        $this->assertTrue(isset($service->name));
        $this->assertSame($test_data['name'], $service->name);

        unset($service->name);

        $this->assertEmpty($service->sharedData()->all());
        $this->assertNull($service->sharedData()->get('test_data'));
        $this->assertNull($service->name);
        $this->assertFalse(isset($service->name));
    }
}
