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

namespace Klein\Exceptions;

use Klein\Route;
use RuntimeException;
use Throwable;

/**
 * RoutePathCompilationException
 *
 * Exception used for when a route's path fails to compile
 */
class RoutePathCompilationException extends RuntimeException implements KleinExceptionInterface
{

    /**
     * Constants
     */

    /**
     * The exception message format
     *
     * @type string
     */
    const MESSAGE_FORMAT = 'Route failed to compile with path "%s".';

    /**
     * The extra failure message format
     *
     * @type string
     */
    const FAILURE_MESSAGE_TITLE_FORMAT = 'Failed with message: "%s"';


    /**
     * Properties
     */

    /**
     * The route that failed to compile
     *
     * @type Route
     */
    protected Route $route;


    /**
     * Methods
     */

    /**
     * Create a RoutePathCompilationException from a route
     * and an optional previous exception
     *
     * @param Route $route The route that failed to compile
     * @param Throwable|null $previous The previous exception
     * @return RoutePathCompilationException
     */
    public static function createFromRoute(Route $route, Throwable $previous = null): RoutePathCompilationException
    {
        $error = (null !== $previous) ? $previous->getMessage() : null;
        $code = (null !== $previous) ? $previous->getCode() : 0;

        $message = sprintf(self::MESSAGE_FORMAT, $route->getPath());
        $message .= ' ' . sprintf(self::FAILURE_MESSAGE_TITLE_FORMAT, $error);

        $exception = new self($message, $code, $previous);
        $exception->setRoute($route);

        return $exception;
    }

    /**
     * Gets the value of route
     *
     * @sccess public
     * @return Route
     */
    public function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * Sets the value of the route
     *
     * @param Route $route
     * @return RoutePathCompilationException
     * @sccess protected
     */
    protected function setRoute(Route $route): RoutePathCompilationException
    {
        $this->route = $route;

        return $this;
    }
}
