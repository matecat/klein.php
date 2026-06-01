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

use Klein\Klein;

/**
 * Really exploiting some functional/global PHP behaviors here. :P
 */
function override_create_fastcgi_function(): void
{
    if (!function_exists('fastcgi_finish_request')) {
        function fastcgi_finish_request(): bool
        {
            echo 'fastcgi_finish_request';
            return false;
        }
    }
    // Nothing to do if already defined
}

if (!function_exists('apc_fetch')) {
    /**
     * Fetch a stored variable from the cache
     * @link https://php.net/manual/en/function.apc-fetch.php
     * @param string|string[] $key The key used to store the value (with apc_store()).
     * If an array is passed then each element is fetched and returned.
     * @param bool|null &$success Set to TRUE in success and FALSE in failure.
     * @return false The stored variable or array of variables on success; FALSE on failure.
     */
    function apc_fetch(string|array $key, bool &$success = null): false
    {
        return false;
    }

    /**
     * Cache a variable in the data store
     * @link https://php.net/manual/en/function.apc-store.php
     * @param string|list<string> $key String: Store the variable using this name. Keys are cache-unique,
     * so storing a second value with the same key will overwrite the original value.
     * Array: Names in key, variables in value.
     * @param mixed $var [optional] The variable to store
     * @param int $ttl [optional]  Time To Live; store var in the cache for ttl seconds. After the ttl has passed,
     * the stored variable will be expunged from the cache (on the next request). If no ttl is supplied
     * (or if the ttl is 0), the value will persist until it is removed from the cache manually,
     * or otherwise fails to exist in the cache (clear, restart, etc.).
     * @return false Returns TRUE on success or FALSE on failure | array with error keys.
     */
    function apc_store(string|array $key, mixed $var, int $ttl = 0): false
    {
        return false;
    }
}

function test_num_args_wrapper(): void
{
    echo func_num_args();
}

function test_response_edit_wrapper(Klein $klein): void
{
    $klein->response()->body('after callbacks!');
}