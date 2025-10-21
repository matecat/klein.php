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

}
