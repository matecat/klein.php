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

namespace Klein;

use Klein\Routes\Route;
use Psr\Cache\CacheItemPoolInterface;

/**
 * AbstractRouteFactory
 *
 * Abstract class for a factory for building new Route instances
 */
abstract class AbstractRouteFactory
{

    /**
     * Properties
     */

    /**
     * The namespace of which to collect the routes in
     * when matching, so you can define routes under a
     * common endpoint
     *
     * @type string
     */
    protected string $namespace = '';

    /**
     * @type CacheItemPoolInterface|null
     */
    protected ?CacheItemPoolInterface $cache;

    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @param ?string $namespace The initial namespace to set
     */
    public function __construct(?string $namespace = '', ?CacheItemPoolInterface $cache = null)
    {
        $this->namespace = $namespace ?? '';
        $this->cache = $cache;
    }

    /**
     * Gets the value of namespace
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Sets the value of namespace
     *
     * @param string $namespace The namespace from which to collect the Routes under
     *
     * @return static
     */
    public function setNamespace(string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Append a namespace to the current namespace
     *
     * @param string $namespace The namespace from which to collect the Routes under
     *
     * @return static
     */
    public function appendNamespace(string $namespace): static
    {
        $this->namespace .= $namespace;

        return $this;
    }

    /**
     * Build factory method
     *
     * This method should be implemented to return a Route instance
     *
     * @param callable $callback Callable callback method to execute on route match
     * @param string $path Route URI path to match
     * @param string|array<string>|null $method HTTP Method to match
     * @param boolean $count_match Whether to count the route as a match when counting total matches
     * @param string|null $name The name of the route
     *
     * @return Route
     */
    abstract public function build(
        callable $callback,
        string $path = '',
        string|array|null $method = null,
        bool $count_match = true,
        ?string $name = null
    ): Route;
}
