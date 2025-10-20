<?php
/**
 * The RadixRouteIndex class is responsible for managing a radix tree structure,
 * which can be used for efficient route matching and management of string paths.
 *
 * @author Domenico Lupinetti (Ostico) domenico@translated.net / ostico@gmail.com
 * Date: 18/10/25
 * Time: 13:15
 *
 */

namespace Klein\Tree;

use Klein\Routes\Route;

class RadixRouteIndex implements IndexInterface
{

    /**
     * @var array<string,array<string,Route>> The radix tree structure for storing routes.
     * - First-level key: literal prefix (e.g., "/users", "/posts/2024")
     * - Second-level key: route hash
     * - Value: Route instance
     */
    protected array $radixTree;
    /**
     * @var array<Route> The catch-all route configuration.
     * - Routes that can't be indexed by a stable literal prefix (e.g., "*", custom regex).
     * - Keyed by route hash.
     */
    protected array $catchAllRoute;

    /**
     * Constructor for initializing route indexes.
     *
     * @param array $radixTree An optional array representing the radix tree structure for route storage.
     * @param array $catchAllRoute An optional array for defining a catch-all route.
     *
     */
    public function __construct(
        array $radixTree = [],
        array $catchAllRoute = []
    ) {
        // Initialize the route indexes
        $this->radixTree = $radixTree;
        $this->catchAllRoute = $catchAllRoute;
    }

    /**
     * Retrieves the catch-all route configuration.
     *
     * @return array<string, Route> Routes that are matched without a literal prefix.
     */
    public function getCatchAllRoute(): array
    {
        return $this->catchAllRoute;
    }

    public function addRoute(Route $route): void
    {
        // Normalize the stored path:
        // - For non-wildcard paths, ensure they start with "/"
        // - Keep "*" (NULL_PATH_VALUE) as-is
        $path = $route->path != $route::NULL_PATH_VALUE ? '/' . $route->path : $route->path;

        // Extract the longest literal prefix before any dynamic/regex token.
        // Split on the first occurrence of characters that start dynamic parts:
        // [ ( . ? + * {        -> regex/meta for params/regex routes
        // Take the static prefix portion (index 0) or empty string.
        $literalPrefixParts = preg_split('`[\[(.?+*{]`', $path, 2)[0] ?: '';

        // If no usable literal prefix, or the route is a custom regex, index it as a catch-all.
        if ($literalPrefixParts == '' || $route->isCustomRegex) {
            $this->catchAllRoute[$route->hash] = $route;
            return;
        }

        // Index route under its full literal prefix, keyed by its unique hash.
        $this->radixTree[$literalPrefixParts][$route->hash] = $route;

        // Build parent-prefix links so lookups can traverse shorter prefixes quickly.
        // Example: for "/users/2024", also link "/users" and "/".
        $prefix = $literalPrefixParts;

        // Split the literal prefix into path segments by "/".
        // Example: "/users/2024" -> ["", "users", "2024"]
        $segments = explode('/', $literalPrefixParts);

        // Iterate upward through the path by popping the last segment each time,
        // creating parent → child references in the radix tree.
        while (array_pop($segments) !== null) {
            // If we've removed all segments, we're at the top; stop.
            if (empty($segments)) {
                break;
            }
            // Reconstruct the parent prefix from remaining segments (ensure "/" for root).
            $parent = implode('/', $segments);
            if ($parent === '') {
                $parent = '/';
            }

            // Prevents an infinite loop when climbing the prefix chain: (Exception: recursion detected)
            // If rebuilding the "parent" prefix produced the same string as the current prefix,
            // there's no shorter path segment to move to, so we stop the loop.
            if ($parent == $prefix) {
                break;
            }

            // Create a reference from the parent bucket to the current child bucket if missing.
            // This lets lookups at shorter prefixes reuse the same child routes.
            if (!isset($this->radixTree[$parent][$prefix])) {
                $this->radixTree[$parent][$prefix] = &$this->radixTree[$prefix];
            } else {
                // Link already exists; higher parents will also be linked, so stop.
                break;
            }

            // Move the cursor up to the parent and continue.
            $prefix = $parent;
        }
    }

    /**
     * Matches the given URI against predefined routes and determines the longest common prefix.
     *
     * @param string $uri The URI to match (normalized to start with "/").
     * @return Route[] Flat map of candidate routes keyed by route hash, aggregated across prefixes.
     */
    public function findPossibleRoutes(string $uri): array
    {
        // Normalize: ensure the URI begins with "/" so prefix lookups are consistent.
        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        // Tokenize the path to allow building decreasing prefixes.
        // Example: "/a/b/c" -> ["", "a", "b", "c"] then searched as "/a/b/c", "/a/b", "/a", "/".
        $paths = explode('/', $uri);

        // Search from the longest path down to the root to find the first matching prefix.
        $arraySize = count($paths);
        for ($i = 0; $i < $arraySize; $i++) {
            // Build the current prefix by slicing off i trailing segments.
            $prefix = implode('/', array_slice($paths, 0, $arraySize - $i));
            if ($prefix == '') {
                $prefix = '/';
            }

            // Query the radix index for all routes under this prefix (deep collection).
            $tmpCommonPrefix = $this->explorePrefix($prefix);

            // If any candidates exist, this is the longest matching prefix; return them.
            if (count($tmpCommonPrefix) !== 0) {
                return $tmpCommonPrefix;
            }
        }

        // No matching prefix found.
        return [];
    }

    /**
     * Explores the given prefix in the radix tree and collects associated routes.
     *
     * @param string $prefix The literal prefix to search.
     * @return array<string,Route> Flat map of routes under the prefix (recursively via references).
     */
    private function explorePrefix(string $prefix): array
    {
        $collector = [];
        // Fetch the subtree (or empty) for the given prefix.
        $indexedPrefix = $this->radixTree[$prefix] ?? [];
        // Walk the nested arrays and collect Route instances keyed by their hash.
        array_walk_recursive($indexedPrefix, function (Route $route) use (&$collector) {
            $collector[$route->hash] = $route;
        });
        return $collector;
    }

}