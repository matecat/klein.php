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

use Klein\DataCollection\HeaderDataCollection;
use Klein\DataCollection\ResponseCookieDataCollection;
use Klein\Exceptions\LockedResponseException;
use Klein\Exceptions\ResponseAlreadySentException;
use Klein\HttpStatus;
use Klein\Request;
use Klein\Response;
use Klein\ResponseCookie;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use RuntimeException;
use InvalidArgumentException;

/**
 * ResponseTest
 */
class ResponseTest extends AbstractKleinTestCase
{

    /**
     * @throws LockedResponseException
     */
    public function testProtocolVersionGetSet(): void
    {
        $version_reg_ex = '/^[0-9]\.[0-9]$/';

        // Empty constructor
        $response = new Response();

        $this->assertIsString($response->protocolVersion());
        $this->assertMatchesRegularExpression($version_reg_ex, $response->protocolVersion());

        // Set in method
        $response = new Response();
        $response->protocolVersion('2.0');

        $this->assertSame('2.0', $response->protocolVersion());
    }

    /**
     * @throws LockedResponseException
     */
    public function testBodyGetSet(): void
    {
        // Empty constructor
        $response = new Response();

        $this->assertEmpty($response->body());

        // Body set in constructor
        $response = new Response('dog');

        $this->assertSame('dog', $response->body());

        // Body set in method
        $response = new Response();
        $response->body('testing');

        $this->assertSame('testing', $response->body());
    }

    /**
     * @throws LockedResponseException
     */
    public function testCodeGetSet(): void
    {
        // Empty constructor
        $response = new Response();

        $this->assertIsInt($response->code());

        // Code set in constructor
        $response = new Response(null, 503);

        $this->assertSame(503, $response->code());

        // Code set in method
        $response = new Response();
        $response->code(204);

        $this->assertSame(204, $response->code());
    }

    /**
     * @throws LockedResponseException
     */
    public function testStatusGetter(): void
    {
        $response = new Response();

        $this->assertInstanceOf(HttpStatus::class, $response->status());
    }

    /**
     * @throws LockedResponseException
     */
    public function testHeadersGetter(): void
    {
        $response = new Response();

        $this->assertInstanceOf(HeaderDataCollection::class, $response->headers());
    }

    /**
     * @throws LockedResponseException
     */
    public function testCookiesGetter(): void
    {
        $response = new Response();

        $this->assertInstanceOf(ResponseCookieDataCollection::class, $response->cookies());
    }

    /**
     * @throws LockedResponseException
     */
    public function testPrepend(): void
    {
        $response = new Response('ein');
        $response->prepend('Kl');

        $this->assertSame('Klein', $response->body());
    }

    /**
     * @throws LockedResponseException
     */
    public function testAppend(): void
    {
        $response = new Response('Kl');
        $response->append('ein');

        $this->assertSame('Klein', $response->body());
    }

    /**
     * @throws LockedResponseException
     */
    public function testLockToggleAndGetters(): void
    {
        $response = new Response();

        $this->assertFalse($response->isLocked());

        $response->lock();

        $this->assertTrue($response->isLocked());

        $response->unlock();

        $this->assertFalse($response->isLocked());
    }

    /**
     * @throws LockedResponseException
     */
    public function testLockedNotModifiable(): void
    {
        $response = new Response();
        $response->lock();

        // Get initial values
        $protocol_version = $response->protocolVersion();
        $body = $response->body();
        $code = $response->code();

        // Attempt to modify
        try {
            $response->protocolVersion('2.0');
        } catch (LockedResponseException $e) {
        }

        try {
            $response->body('WOOT!');
        } catch (LockedResponseException $e) {
        }

        try {
            $response->code(204);
        } catch (LockedResponseException $e) {
        }

        try {
            $response->prepend('cat');
        } catch (LockedResponseException $e) {
        }

        try {
            $response->append('dog');
        } catch (LockedResponseException $e) {
        }


        // Assert nothing has changed
        $this->assertSame($protocol_version, $response->protocolVersion());
        $this->assertSame($body, $response->body());
        $this->assertSame($code, $response->code());
    }

    /**
     * Testing headers is a pain in the ass. ;)
     *
     * Technically... we can't. So, yea.
     *
     * Attempt to run in a separate process so we can
     * at least call our internal methods
     * @throws LockedResponseException
     */
    public function testSendHeaders(): void
    {
        $response = new Response('woot!');
        $response->headers()->set('test', 'sure');
        $response->headers()->set('Authorization', 'Basic asdasd');

        $response->sendHeaders();

        $this->expectOutputString('');
    }

    /**
     * @throws LockedResponseException
     */
    #[RunInSeparateProcess]
    public function testSendHeadersInIsolateProcess(): void
    {
        $this->testSendHeaders();
    }

    /**
     * Testing cookies is exactly like testing headers
     * ... So, yea.
     * @throws LockedResponseException
     */
    public function testSendCookies(): void
    {
        $response = new Response();
        $response->cookies()->set('test', 'woot!');
        $response->cookies()->set('Cookie-name', 'wtf?');

        $response->sendCookies();

        $this->expectOutputString('');
    }

    /**
     * @throws LockedResponseException
     */
    #[RunInSeparateProcess]
    public function testSendCookiesInIsolateProcess(): void
    {
        $this->testSendCookies();
    }

    /**
     * @throws LockedResponseException
     */
    public function testSendBody(): void
    {
        $response = new Response('woot!');
        $response->sendBody();

        $this->expectOutputString('woot!');
    }

    /**
     * @throws LockedResponseException
     * @throws ResponseAlreadySentException
     */
    public function testSend(): void
    {
        $response = new Response('woot!');
        $response->send();

        $this->expectOutputString('woot!');
        $this->assertTrue($response->isLocked());
    }

    /**
     * @throws LockedResponseException
     * @throws ResponseAlreadySentException
     */
    public function testSendWhenAlreadySent(): void
    {
        $this->expectException(ResponseAlreadySentException::class);
        $response = new Response();
        $response->send();

        $this->assertTrue($response->isLocked());

        $response->send();
    }

    /**
     * This uses some crazy exploitation to make sure that the
     * `fastcgi_finish_request()` function gets called.
     * Because of this, this MUST be run in a separate process
     *
     * @throws LockedResponseException
     * @throws ResponseAlreadySentException
     */
    #[RunInSeparateProcess]
    public function testSendCallsFastCGIFinishRequest(): void
    {
        // Custom fastcgi function
        override_create_fastcgi_function();

        $this->expectOutputString('fastcgi_finish_request');

        $response = new Response();
        $response->send();
    }

    /**
     * @throws LockedResponseException
     */
    public function testChunk(): void
    {
        $content = [
            'initial content',
            'more',
            'content',
        ];

        $response = new Response($content[0]);

        $response->chunk();
        $response->chunk($content[1]);
        $response->chunk($content[2]);

        $this->expectOutputString(
            dechex(strlen($content[0])) . "\r\n"
            . "$content[0]\r\n"
            . dechex(strlen($content[1])) . "\r\n"
            . "$content[1]\r\n"
            . dechex(strlen($content[2])) . "\r\n"
            . "$content[2]\r\n"
        );
    }

    /**
     * @throws LockedResponseException
     */
    public function testHeader(): void
    {
        $headers = [
            'test' => 'sure',
            'okay' => 'yup',
        ];

        $response = new Response();

        // Make sure the headers are initially empty
        $this->assertEmpty($response->headers()->all());

        // Set the headers
        foreach ($headers as $name => $value) {
            $response->header($name, $value);
        }

        $this->assertNotEmpty($response->headers()->all());

        // Set the headers
        foreach ($headers as $name => $value) {
            $this->assertSame($value, $response->headers()->get($name));
        }
    }

    /**
     * @throws LockedResponseException
     */
    #[Group("testCookie")]
    public function testCookie(): void
    {
        $test_cookie_data = [
            'name' => 'name',
            'value' => 'value',
            'expiry' => null,
            'path' => '/path',
            'domain' => 'whatever.com',
            'secure' => true,
            'httponly' => true
        ];

        $test_cookie = new ResponseCookie(
            $test_cookie_data['name'],
            $test_cookie_data['value'],
            $test_cookie_data['expiry'],
            $test_cookie_data['path'],
            $test_cookie_data['domain'],
            $test_cookie_data['secure'],
            $test_cookie_data['httponly']
        );

        $response = new Response();

        // Make sure the cookies are initially empty
        $this->assertEmpty($response->cookies()->all());

        // Set a cookies
        $response->cookie(
            $test_cookie_data['name'],
            $test_cookie_data['value'],
            $test_cookie_data['expiry'],
            $test_cookie_data['path'],
            $test_cookie_data['domain'],
            $test_cookie_data['secure'],
            $test_cookie_data['httponly']
        );

        $this->assertNotEmpty($response->cookies()->all());

        $the_cookie = $response->cookies()->get($test_cookie_data['name']);

        $this->assertInstanceOf(ResponseCookie::class, $the_cookie);
        $this->assertSame($test_cookie_data['name'], $the_cookie->getName());
        $this->assertSame($test_cookie_data['value'], $the_cookie->getValue());
        $this->assertSame($test_cookie_data['path'], $the_cookie->getPath());
        $this->assertSame($test_cookie_data['domain'], $the_cookie->getDomain());
        $this->assertSame($test_cookie_data['secure'], $the_cookie->getSecure());
        $this->assertSame($test_cookie_data['httponly'], $the_cookie->getHttpOnly());
        $this->assertGreaterThan(0, $the_cookie->getExpire());
    }

    /**
     * @throws LockedResponseException
     */
    public function testNoCache(): void
    {
        $response = new Response();

        // Make sure the headers are initially empty
        $this->assertEmpty($response->headers()->all());

        $response->noCache();

        $this->assertContains('no-cache', $response->headers()->all());
    }

    /**
     * @throws LockedResponseException
     */
    public function testRedirect(): void
    {
        $url = 'http://google.com/';
        $code = 302;

        $response = new Response();
        $response->redirect($url, $code);

        $this->assertSame($code, $response->code());
        $this->assertSame($url, $response->headers()->get('Location'));
        $this->assertTrue($response->isLocked());
    }

    /**
     * @throws LockedResponseException
     */
    public function testDump(): void
    {
        $response = new Response();

        $this->assertEmpty($response->body());

        $response->dump('test');

        $body = $response->body();
        $this->assertIsString($body);
        $this->assertStringContainsString('test', $body);
    }

    /**
     * @throws LockedResponseException
     */
    public function testDumpArray(): void
    {
        $response = new Response();

        $this->assertEmpty($response->body());

        $response->dump(['sure', 1, 10, 17, 'ok' => 'no']);

        $this->assertNotEmpty($response->body());
        $this->assertNotEquals('<pre></pre>', $response->body());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFileSend(): void
    {
        $file_name = 'testing';
        $file_mime = 'text/plain';

        $this->klein_app->respond(
            callback: function (Request $request, Response $response, $service) use ($file_name, $file_mime) {
                $response->file(__FILE__, $file_name, $file_mime);
            }
        );

        $this->klein_app->dispatch();

        // Expect our output to match our file
        $this->expectOutputString(
            (string) file_get_contents(__FILE__)
        );

        // Assert headers were passed
        $this->assertEquals(
            $file_mime,
            $this->klein_app->response()->headers()->get('Content-Type')
        );
        $this->assertEquals(
            filesize(__FILE__),
            $this->klein_app->response()->headers()->get('Content-Length')
        );
        $this->assertStringContainsString(
            $file_name,
            (string) $this->klein_app->response()->headers()->get('Content-Disposition')
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFileSendLooseArgs(): void
    {
        $this->klein_app->respond(
            callback: function ($request, $response, $service) {
                $response->file(__FILE__);
            }
        );

        $this->klein_app->dispatch();

        // Expect our output to match our file
        $this->expectOutputString(
            (string) file_get_contents(__FILE__)
        );

        // Assert headers were passed
        $this->assertEquals(
            filesize(__FILE__),
            $this->klein_app->response()->headers()->get('Content-Length')
        );
        $this->assertNotNull(
            $this->klein_app->response()->headers()->get('Content-Type')
        );
        $this->assertNotNull(
            $this->klein_app->response()->headers()->get('Content-Disposition')
        );
    }

    /**
     * @throws LockedResponseException
     * @throws RuntimeException
     */
    public function testFileSendWhenAlreadySent(): void
    {
        $this->expectException(ResponseAlreadySentException::class);
        // Expect our output to match our file
        $this->expectOutputString(
            (string) file_get_contents(__FILE__)
        );

        $response = new Response();
        $response->file(__FILE__);

        $this->assertTrue($response->isLocked());

        $response->file(__FILE__);
    }

    /**
     * @throws LockedResponseException
     * @throws RuntimeException
     */
    public function testFileSendWithNonExistentFile(): void
    {
        $this->expectException(RuntimeException::class);
        // Ignore the file warning
        $old_error_val = error_reporting();
        error_reporting(E_ALL ^ E_WARNING);

        $response = new Response();
        $response->file(__DIR__ . '/some/bogus/path/that/does/not/exist');

        error_reporting($old_error_val);
    }

    /**
     * This uses some crazy exploitation to make sure that the
     * `fastcgi_finish_request()` function gets called.
     * Because of this, this MUST be run in a separate process
     *
     * @throws LockedResponseException
     * @throws RuntimeException
     */
    #[RunInSeparateProcess]
    public function testFileSendCallsFastCGIFinishRequest(): void
    {
        // Custom fastcgi function
        override_create_fastcgi_function();

        // Expect our output to match our file
        $this->expectOutputString(
            (string) file_get_contents(__FILE__) . 'fastcgi_finish_request'
        );

        $response = new Response();
        $response->file(__FILE__);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testJSON(): void
    {
        // Create a test object to be JSON encoded/decoded
        $test_object = (object)[
            'cheese',
            'dog' => 'bacon',
            '1.5' => 'should be 1 (thanks PHP casting...)',
            'integer' => 1,
            'double' => 1.5,
            '_weird' => true,
            'uniqid' => uniqid(),
        ];

        $this->klein_app->respond(
            callback: function ($request, $response, $service) use ($test_object) {
                $response->json($test_object);
            }
        );

        $this->klein_app->dispatch();

        // Expect our output to match our json encoded test object
        $this->expectOutputString(
            (string) json_encode($test_object)
        );

        // Assert headers were passed
        $this->assertEquals(
            'no-cache',
            $this->klein_app->response()->headers()->get('Pragma')
        );
        $this->assertEquals(
            'no-store, no-cache',
            $this->klein_app->response()->headers()->get('Cache-Control')
        );
        $this->assertEquals(
            'application/json',
            $this->klein_app->response()->headers()->get('Content-Type')
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testJSONWithPrefix(): void
    {
        // Create a test object to be JSON encoded/decoded
        $test_object = [
            'cheese',
        ];
        $prefix = 'dogma';

        $this->klein_app->respond(
            callback: function ($request, $response, $service) use ($test_object, $prefix) {
                $response->json($test_object, $prefix);
            }
        );

        $this->klein_app->dispatch();

        // Expect our output to match our json encoded test object
        $this->expectOutputString(
            'dogma(' . json_encode($test_object) . ');'
        );

        // Assert headers were passed
        $this->assertEquals(
            'no-cache',
            $this->klein_app->response()->headers()->get('Pragma')
        );
        $this->assertEquals(
            'no-store, no-cache',
            $this->klein_app->response()->headers()->get('Cache-Control')
        );
        $this->assertEquals(
            'text/javascript',
            $this->klein_app->response()->headers()->get('Content-Type')
        );
    }
}
