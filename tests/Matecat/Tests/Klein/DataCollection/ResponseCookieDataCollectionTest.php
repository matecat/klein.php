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

namespace Matecat\Tests\Klein\DataCollection;

use Klein\DataCollection\ResponseCookieDataCollection;
use Klein\ResponseCookie;
use Matecat\Tests\Klein\AbstractKleinTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * ResponseCookieDataCollectionTest
 */
class ResponseCookieDataCollectionTest extends AbstractKleinTestCase
{
    /*
     * Data Providers and Methods
     */

    /**
     * @return array{0: array{ResponseCookie, ResponseCookie}}
     */
    public static function sampleDataProvider(): array
    {
        $sample_cookie = new ResponseCookie(
            'Trevor',
            'is a programmer',
            3600,
            '/',
            'example.com',
            false,
            false
        );

        $sample_other_cookie = new ResponseCookie(
            'Chris',
            'is a boss',
            60,
            '/app/',
            'github.com',
            true,
            true
        );

        return [
            [$sample_cookie, $sample_other_cookie],
        ];
    }


    /*
     * Tests
     */

    #[DataProvider('sampleDataProvider')]
    public function testSet(ResponseCookie $sample_cookie, ResponseCookie $sample_other_cookie): void
    {
        // Create our collection with NO data
        $data_collection = new ResponseCookieDataCollection();

        // Set our data from our test data
        $data_collection->set('first', $sample_cookie);

        $this->assertSame($sample_cookie, $data_collection->get('first'));
        $this->assertInstanceOf(ResponseCookie::class, $data_collection->get('first'));
    }

    public function testSetStringConvertsToCookie(): void
    {
        // Create our collection with NO data
        $data_collection = new ResponseCookieDataCollection();

        // Set our data from our test data
        $data_collection->set('first', 'value');

        $this->assertNotSame('value', $data_collection->get('first'));
        $this->assertInstanceOf(ResponseCookie::class, $data_collection->get('first'));
    }

    #[DataProvider('sampleDataProvider')]
    public function testConstructorRoutesThroughSet(
        ResponseCookie $sample_cookie,
        ResponseCookie $sample_other_cookie
    ): void {
        $array_of_cookie_instances = [
            $sample_cookie,
            $sample_other_cookie,
            new ResponseCookie('test'),
        ];

        // Create our collection with NO data
        $data_collection = new ResponseCookieDataCollection($array_of_cookie_instances);
        $this->assertSame($array_of_cookie_instances, array_values($data_collection->all()));

        foreach ($data_collection as $cookie) {
            $this->assertInstanceOf(ResponseCookie::class, $cookie);
        }
    }
}
