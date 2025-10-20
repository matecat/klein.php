<?php
/**
 * The TreeHandler class is responsible for managing a radix tree structure,
 * which can be used for efficient route matching and management of string paths.
 */

namespace Klein\Tree;

use Klein\Routes\Route;

class TreeHandler
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
     * @param array<string, Route> $radixTree Initial radix tree (optional).
     * @param array<string, Route> $catchAllRoute Initial catch-all routes (optional).
     */
    public function __construct(array $radixTree = [], array $catchAllRoute = [])
    {
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
            $this->catchAllRoute[$route->getHash()] = $route;
            return;
        }

        // Index route under its full literal prefix, keyed by its unique hash.
        $this->radixTree[$literalPrefixParts][$route->getHash()] = $route;

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

//        for ($i = strlen($literalPrefixParts); $i > 0; $i--) {
//            $prefix_prev = substr($literalPrefixParts, 0, $i - 1);
//
//            // Stop when we exceed the root.
//            if ($prefix_prev == '') {
//                break;
//            }
//
//            // Create a reference from the shorter prefix to the longer one if missing.
//            // This effectively creates a radix-like parent-child chain via array references.
//            if (!isset($index[$prefix_prev][$prefix])) {
//                $this->radixTree[$prefix_prev][$prefix] = &$this->radixTree[$prefix];
//            } else {
//                // Already linked; no need to continue walking up.
//                break;
//            }
//
//            $prefix = $prefix_prev;
//        }
    }

    /**
     * Matches the given URI against predefined routes and determines the longest common prefix.
     *
     * @param string $uri The URI to match (normalized to start with "/").
     * @return Route[] Flat map of candidate routes keyed by route hash, aggregated across prefixes.
     */
    public function matchRoute(string $uri): array
    {
        // Ensure URI starts with "/" for consistent prefixing.
        if (stripos($uri, '/') !== 0) {
            $uri = '/' . $uri;
        }

        // Split URI by "/" to progressively build prefixes:
        // "/a/b/c" -> ["/a", "/a/b", "/a/b/c"]
        $paths = explode('/', $uri);

        $longestCommonPrefix = [];
        for ($i = 0; $i < count($paths); $i++) { // TODO: review edge-case for root "/"
            $prefix = implode('/', array_slice($paths, 0, $i + 1));
            if ($prefix == '') {
                $prefix = '/';
            }
            // Collect all routes indexed under this prefix (including descendants via references).
            $tmpCommonPrefix = $this->explorePrefix($prefix);
            $newSize = count($tmpCommonPrefix);
            // Stop once a step yields no candidates.
            if ($newSize == 0) {
                break;
            }
            // Merge candidates while preserving earlier keys (route hashes).
            $longestCommonPrefix = $longestCommonPrefix + $tmpCommonPrefix;
        }

        return $longestCommonPrefix;
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
        array_walk_recursive($indexedPrefix, function ($route) use (&$collector) {
            $collector[$route->getHash()] = $route;
        });
        return $collector;
    }

}