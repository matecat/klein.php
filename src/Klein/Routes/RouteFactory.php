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

namespace Klein\Routes;

use Klein\AbstractRouteFactory;

/**
 * RouteFactory
 *
 * The default implementation of the AbstractRouteFactory
 */
class RouteFactory extends AbstractRouteFactory
{

    /**
     * Build a Route instance
     *
     * @param callable $callback Callable callback method to execute on route match
     * @param string|null $path Route URI path to match
     * @param string|string[]|null $method HTTP Method to match
     * @param boolean $count_match Whether to count the route as a match when counting total matches.
     *                              The parameter is in the signature,
     *                              but for Klein default route implementation it is ignored.
     *                              Route object will set it based on the path value.
     * @param string|null $name The name of the route
     *
     * @return Route
     */
    public function build(
        callable $callback,
        ?string $path = '*',
        string|array|null $method = null,
        bool $count_match = true,
        ?string $name = null
    ): Route {
        return new Route(
            $callback,
            $path,
            $method,
            $this->namespace,
            null, // count_match is ignored for Klein default route implementation
            $this->cache,
            $name
        );
    }
}
