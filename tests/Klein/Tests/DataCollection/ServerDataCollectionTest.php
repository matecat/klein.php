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

namespace Klein\Tests\DataCollection;

use Klein\DataCollection\ServerDataCollection;
use Klein\Tests\AbstractKleinTestCase;

/**
 * ServerDataCollectionTest
 */
class ServerDataCollectionTest extends AbstractKleinTestCase
{

    /*
     * Data Providers and Methods
     */

    /*
     * Tests
     */

    public function testHasPrefix()
    {
        $this->assertTrue(ServerDataCollection::hasPrefix('dog_wierd', 'dog'));
        $this->assertTrue(ServerDataCollection::hasPrefix('_dog_wierd', '_dog'));
        $this->assertFalse(ServerDataCollection::hasPrefix('_dog_wierd', 'dog'));
    }

}
