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

namespace Klein\Tests\DataCollection;

use Klein\DataCollection\HeaderDataCollection;
use Klein\Tests\AbstractKleinTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * HeaderDataCollectionTest
 */
class HeaderDataCollectionTest extends AbstractKleinTestCase
{

    /**
     * Non existent key in the sample data
     *
     * @type string
     */
    protected static $nonexistent_key = 'non-standard-header';


    /*
     * Data Providers and Methods
     */

    /**
     * Quickly makes sure that no sample data arrays
     * have any keys that match the "nonexistent_key"
     *
     * @param array $sample_data
     *
     * @return void
     */
    protected static function prepareSampleData(&$sample_data)
    {
        if (isset($sample_data[static::$nonexistent_key])) {
            unset($sample_data[static::$nonexistent_key]);
        }

        foreach ($sample_data as &$data) {
            if (is_array($data)) {
                static::prepareSampleData($data);
            }
        }
        reset($sample_data);
    }

    /**
     * Sample data provider
     *
     * @return array
     */
    public static function sampleDataProvider()
    {
        // Populate our sample data
        $sample_data = [
            'SCRIPT_URL' => '/foobar.php',
            'SCRIPT_URI' => 'https://localhost/foobar.php',
            'HTTP_AUTHORIZATION' => '',
            'no-gzip' => '1',
            'HTTPS' => 'on',
            'SSL_TLS_SNI' => 'localhost',
            'HTTP_HOST' => 'localhost',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_PRAGMA' => 'no-cache',
            'HTTP_CACHE_CONTROL' => 'no-cache',
            'HTTP_SEC_CH_UA' => '"Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"',
            'HTTP_SEC_CH_UA_MOBILE' => '?0',
            'HTTP_SEC_CH_UA_PLATFORM' => '"Linux"',
            'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'HTTP_SEC_FETCH_SITE' => 'none',
            'HTTP_SEC_FETCH_MODE' => 'navigate',
            'HTTP_SEC_FETCH_USER' => '?1',
            'HTTP_SEC_FETCH_DEST' => 'document',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br, zstd',
            'HTTP_ACCEPT_LANGUAGE' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6',
            'HTTP_COOKIE' => 'PHPSESSID=XXXX',
            'CONTENT_TYPE' => 'application/json', // <<----- Notice content type
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            'SERVER_SIGNATURE' => '',
            'SERVER_SOFTWARE' => 'Apache/2.4.58 (Ubuntu)',
            'SERVER_NAME' => 'localhost',
            'SERVER_ADDR' => '172.19.0.9',
            'SERVER_PORT' => '80',
            'REMOTE_ADDR' => '172.19.0.1',
            'DOCUMENT_ROOT' => '/var/www/matecat',
            'REQUEST_SCHEME' => 'https',
            'CONTEXT_PREFIX' => '',
            'CONTEXT_DOCUMENT_ROOT' => '/var/www',
            'SERVER_ADMIN' => 'webmaster@localhost',
            'SCRIPT_FILENAME' => '/var/www/foobar.php',
            'REMOTE_PORT' => '55400',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/foobar.php',
            'SCRIPT_NAME' => '/foobar.php',
            'PHP_SELF' => '/foobar.php',
            'REQUEST_TIME_FLOAT' => 1761049032.533455,
            'REQUEST_TIME' => 1761049032,
            'HTTP_CONTENT_TOP' => 'Fake',
        ];

        static::prepareSampleData($sample_data);

        $data_collection = new HeaderDataCollection($sample_data);

        return [
            [$sample_data, $data_collection],
        ];
    }


    /*
     * Tests
     */

    #[DataProvider('sampleDataProvider')]
    public function testConstructorCorrectContentRelatedKeys($sample_data, $data_collection)
    {
        $this->assertSame('application/json', $data_collection->get('Content-Type'));
    }

    #[DataProvider('sampleDataProvider')]
    public function testConstructorCorrectlyFormatted($sample_data, $data_collection)
    {
        $this->assertNotSame($sample_data, $data_collection->all());
        $this->assertArrayNotHasKey('HTTP_HOST', $data_collection->all());
        $this->assertSame($sample_data['HTTP_HOST'], $data_collection->get('Host'));
        $this->assertContains('PHPSESSID=XXXX', $data_collection->all());
    }


    #[DataProvider('sampleDataProvider')]
    public function testGet($sample_data, $data_collection)
    {
        $default = 'WOOT!';

        $this->assertSame($sample_data['HTTP_USER_AGENT'], $data_collection->get('User-Agent'));
        $this->assertSame($default, $data_collection->get(static::$nonexistent_key, $default));
        $this->assertNull($data_collection->get(static::$nonexistent_key));
    }

    public function testSet()
    {
        // Test data
        $data = [
            'DOG_NAME' => 'cooper',
        ];

        // Create our collection with NO data
        $data_collection = new HeaderDataCollection();

        // Set our data from our test data
        $data_collection->set(key($data), current($data));

        // Make sure the set worked, but the key is different
        $this->assertSame(current($data), $data_collection->get(key($data)));
        $this->assertArrayHasKey(key($data), $data_collection->all());
    }

    #[DataProvider('sampleDataProvider')]
    public function testExists($sample_data, $data_collection)
    {
        // Make sure the set worked, but the key is different
        $this->assertTrue($data_collection->exists('Host'));
        $this->assertFalse($data_collection->exists(static::$nonexistent_key));
    }

    #[DataProvider('sampleDataProvider')]
    public function testRemove($sample_data, $data_collection)
    {
        $this->assertTrue($data_collection->exists('Host'));

        $data_collection->remove('Host');

        $this->assertFalse($data_collection->exists('Host'));
    }


    public function testNameNotNormalizing()
    {
        // Test data
        $header = [
            'HTTP_content_TYPE' => 'application/json'
        ];
        $normalized_key = new HeaderDataCollection($header);

        $this->assertNotSame($header, $normalized_key->all());

        $this->assertNotEmpty($normalized_key->all());
        $this->assertSame('application/json', $normalized_key->get('Content-Type'));
    }
}
