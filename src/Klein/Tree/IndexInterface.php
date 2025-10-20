<?php
/**
 * Interface IndexInterface
 *
 * Defines the structure for managing and retrieving route data in a tree-based structure.
 *
 * @author Domenico Lupinetti (Ostico) domenico@translated.net / ostico@gmail.com
 * Date: 18/10/25
 * Time: 13:15
 *
 */

namespace Klein\Tree;

/**
 * Represents a single route in the Klein routing system. A route defines a specific
 * path or set of paths that will trigger certain behaviors or actions on an HTTP request.
 *
 * The `Route` class facilitates configuration for the route, such as specifying
 * its path, HTTP methods, middleware, callbacks, and parameter requirements.
 *
 * It is used to match incoming requests based on the defined constraints and execute
 * the corresponding logic for the matched route.
 */

use Klein\Routes\Route;

/**
 * Provides an interface for indexing and managing routes.
 */
interface IndexInterface
{

    /**
     * Finds and returns a list of possible routes based on the provided URI.
     *
     * @param string $uri The URI for which possible routes need to be determined.
     * @return Route[] An array of possible routes corresponding to the given URI.
     */
    public function findPossibleRoutes(string $uri): array;

    /**
     * Adds a route to the routing collection.
     *
     * @param Route $route The route object to be added.
     * @return void
     */
    public function addRoute(Route $route): void;

}