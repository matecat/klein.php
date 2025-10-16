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

use BadMethodCallException;
use Klein\App;
use Klein\Exceptions\DuplicateServiceException;
use Klein\Exceptions\UnknownServiceException;
use PHPUnit\Framework\Attributes\Depends;

/**
 * AppTest
 */
class AppTest extends AbstractKleinTestCase
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

    public function testRegisterFiller()
    {
        $func_name = 'yay_func';

        $app = new App();

        $app->register($func_name, $this->getTestCallable());

        return array(
            'app' => $app,
            'func_name' => $func_name,
        );
    }

    #[Depends('testRegisterFiller')]
    public function testGet(array $args)
    {
        // Get our vars from our args
        extract($args);

        $returned = $app->$func_name;

        $this->assertNotNull($returned);
        $this->assertSame(self::TEST_CALLBACK_MESSAGE, $returned);
    }

    /**
     * @return void
     */
    public function testGetBadMethod()
    {
        $app = new App();
        $this->expectException(UnknownServiceException::class);
        $app->random_thing_that_doesnt_exist;
    }

    #[Depends('testRegisterFiller')]
    public function testCall(array $args)
    {
        // Get our vars from our args
        extract($args);

        $returned = $app->{$func_name}();

        $this->assertNotNull($returned);
        $this->assertSame(self::TEST_CALLBACK_MESSAGE, $returned);
    }

    public function testCallBadMethod()
    {
        $this->expectException(BadMethodCallException::class);
        $app = new App();
        $app->random_thing_that_doesnt_exist();
    }

    #[Depends('testRegisterFiller')]
    public function testRegisterDuplicateMethod(array $args)
    {
        $this->expectException(DuplicateServiceException::class);
        // Get our vars from our args
        extract($args);

        $app->register($func_name, $this->getTestCallable());
    }
}
