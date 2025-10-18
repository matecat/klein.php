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

use Klein\Routes\Route;

/**
 * RouteCollection
 *
 * A DataCollection for Routes
 */
class RouteCollection extends DataCollection
{

    /**
     * @var bool $is_name_prepared Whether the named routes have been prepared
     */
    protected bool $is_name_prepared = false;

    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @override DataCollection::__construct()
     * @param array<string, Route> $routes The routes of this collection
     */
    public function __construct(array $routes = [])
    {
        parent::__construct();
        foreach ($routes as $value) {
            $this->add($value);
        }
    }

    /**
     * Set a route
     *
     * {@inheritdoc}
     *
     * A value may either be a callable or a Route instance
     * Callable values will be converted into a Route with
     * the "name" of the route being set from the "key"
     *
     * A developer may add a named route to the collection
     * by passing the name of the route as the "$key" and an
     * instance of a Route as the "$value"
     *
     * @param string $key The name of the route to set
     * @param mixed $value The value of the route to set
     *
     * @return static
     * @see DataCollection::set()
     */
    public function set(string $key, mixed $value): static
    {
        if (!$value instanceof Route && is_callable($value)) {
            $value = new Route($value);
        }

        return parent::set($key, $value);
    }

    /**
     * Add a route instance to the collection
     *
     * This will auto-generate a name
     *
     * @param Route $route
     *
     * @return static
     */
    public function addRoute(Route $route): static
    {
        // Adding a new route invalidates any previously prepared name index/cache.
        // Mark as not prepared so that prepareNamed() can rebuild the name mapping on next access.
        $this->is_name_prepared = false;

        /**
         * Auto-generate a name from the object's hash
         * This makes it so that we can autogenerate names
         * that ensure duplicate route instances are overridden
         */
        $name = spl_object_hash($route);

        return $this->set($name, $route);
    }

    /**
     * Add a route to the collection
     *
     * This allows a more generic form that
     * will take a Route instance, string callable
     * or any other Route class compatible callback
     *
     * @param callable|Route $route
     *
     * @return static
     */
    public function add(callable|Route $route): static
    {
        if (!$route instanceof Route) {
            $route = new Route($route);
        }

        return $this->addRoute($route);
    }

    /**
     * Prepare the named routes in the collection
     *
     * This loops through every route to set the collection's
     * key name for that route to equal the route name if it's changed
     *
     * Thankfully, because routes are all objects, this
     * takes little memory as it's simply moving references around
     *
     * @return static
     */
    public function prepareNamed(): static
    {
        if ($this->is_name_prepared) {
            return $this;
        }

        // Create a new collection so we can keep our order
        $prepared = new self();

        foreach ($this as $route) {
            $route_name = $route->getName();

            if (null !== $route_name) {
                // Add the route to the new set with the new name
                $prepared->set($route_name, $route);
            } else {
                $prepared->add($route);
            }
        }

        // Replace our collection's items with our newly prepared collection's items
        $this->replace($prepared->all());

        // Mark the collection as having processed/normalized named routes so we don't
        // repeat the preparation work on further calls to prepareNamed()
        $this->is_name_prepared = true;

        return $this;
    }
}
