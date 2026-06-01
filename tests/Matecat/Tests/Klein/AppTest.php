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

use BadMethodCallException;
use Closure;
use Klein\App;
use Klein\Exceptions\DuplicateServiceException;
use Klein\Exceptions\UnknownServiceException;
use Matecat\Tests\Klein;
use PHPUnit\Framework\Attributes\Depends;

/**
 * AppTest
 */
class AppTest extends Klein\AbstractKleinTestCase
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
     * @throws DuplicateServiceException
     */

    public function testRegisterFiller(): void
    {
        $func_name = 'yay_func';

        $app = new App();

        $app->register($func_name, $this->getTestCallable());

        $this->assertInstanceOf(App::class, $app);
    }

    /**
     * @throws DuplicateServiceException
     */
    public function testGet(): void
    {
        $func_name = 'yay_func';
        $app = new App();
        $app->register($func_name, $this->getTestCallable());

        $returned = $app->$func_name; // @phpstan-ignore property.notFound

        $this->assertNotNull($returned);
        $this->assertSame(self::TEST_CALLBACK_MESSAGE, $returned);
    }

    public function testGetBadMethod(): void
    {
        $app = new App();
        $this->expectException(UnknownServiceException::class);
        $app->random_thing_that_doesnt_exist; // @phpstan-ignore property.notFound, expr.resultUnused
    }

    /**
     * @throws DuplicateServiceException
     */
    public function testCall(): void
    {
        $func_name = 'yay_func';
        $app = new App();
        $app->register($func_name, $this->getTestCallable());

        $returned = $app->{$func_name}(); // @phpstan-ignore method.notFound

        $this->assertNotNull($returned);
        $this->assertSame(self::TEST_CALLBACK_MESSAGE, $returned);
    }

    public function testCallBadMethod(): void
    {
        $this->expectException(BadMethodCallException::class);
        $app = new App();
        $app->random_thing_that_doesnt_exist(); // @phpstan-ignore method.notFound
    }

    /**
     * @throws DuplicateServiceException
     */
    public function testRegisterDuplicateMethod(): void
    {
        $this->expectException(DuplicateServiceException::class);
        $func_name = 'yay_func';
        $app = new App();
        $app->register($func_name, $this->getTestCallable());
        $app->register($func_name, $this->getTestCallable());
    }
}
