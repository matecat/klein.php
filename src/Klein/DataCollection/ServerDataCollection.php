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

namespace Klein\DataCollection;

/**
 * ServerDataCollection
 *
 * A DataCollection for "$_SERVER" like data
 *
 * Look familiar?
 *
 * Inspired by @fabpot's Symfony 2's HttpFoundation
 * @link https://github.com/symfony/HttpFoundation/blob/master/ServerBag.php
 */
class ServerDataCollection extends DataCollection
{

    /**
     * Class properties
     */

    /**
     * The prefix of HTTP headers normally
     * stored in the Server data
     *
     * @type string
     */
    protected static string $http_header_prefix = 'HTTP_';

    /**
     * The list of HTTP headers that for some
     * reason aren't prefixed in PHP...
     *
     * @var string[]
     */
    protected static array $http_nonprefixed_headers = [
        'CONTENT_LENGTH',
        'CONTENT_TYPE',
        'CONTENT_MD5',
    ];

    /**
     * Constructor method to initialize the object with server variables
     *
     * @param array<string, string> $serverVars An associative array of server variables
     *
     * @return void
     */
    public function __construct(array $serverVars = [])
    {
        parent::__construct($serverVars);
    }

    /**
     * Quickly check if a string has a passed prefix
     *
     * @param string $string The string to check
     * @param string $prefix The prefix to test
     *
     * @return boolean
     */
    public static function hasPrefix(string $string, string $prefix): bool
    {
        if (str_starts_with($string, $prefix)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves an array of HTTP headers by processing attributes with specific prefixes
     * or predefined non-prefixed headers.
     *
     * This inspects $this->attributes (typically $_SERVER-like data) and:
     * - Captures all keys that start with the standard HTTP_ prefix, stripping that prefix.
     * - Additionally captures a small allowlist of known header keys that PHP exposes without the HTTP_ prefix
     *   (e.g., CONTENT_LENGTH, CONTENT_TYPE, CONTENT_MD5).
     *
     * Examples:
     * - HTTP_ACCEPT => ACCEPT
     * - HTTP_X_REQUESTED_WITH => X_REQUESTED_WITH
     * - CONTENT_TYPE => CONTENT_TYPE (kept as-is because it’s non-prefixed)
     *
     * @return array<string, string> The array of normalized HTTP headers (keys without the HTTP_ prefix).
     */
    public function getHeaders(): array
    {
        // Initialize the collection that will store normalized header names and their values.
        $headers = [];

        // Iterate over all server-like attributes (key/value pairs).
        foreach ($this->attributes as $key => $value) {
            // Case 1: Keys that start with the configured HTTP header prefix (e.g., "HTTP_").
            // Normalize by removing the prefix so "HTTP_ACCEPT" becomes "ACCEPT".
            if (self::hasPrefix($key, self::$http_header_prefix)) {
                $headers[substr($key, strlen(self::$http_header_prefix))] = $value;

                // Case 2: Specific headers that PHP exposes without the "HTTP_" prefix.
                // Keep these keys unchanged (e.g., "CONTENT_TYPE").
            } elseif (in_array($key, self::$http_nonprefixed_headers, true)) {
                $headers[$key] = $value;
            }
        }

        // Return the collected headers in normalized form.
        return $headers;
    }

}
