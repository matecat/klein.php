<?php
/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 18/10/25
 * Time: 13:15
 *
 */

namespace Klein\Routes;

use Klein\Exceptions\RegularExpressionCompilationException;

class RouteCompiler
{

    /**
     * The regular expression used to compile and match URL's
     *
     * @type string
     */
    private const string ROUTE_COMPILE_REGEX = '`(\\\?(?:/|\.|))\[([^:\]]*)(?::([^:\]]*))?](\?|)`';

    /**
     * The regular expression used to escape the non-named param section of a route URL
     *
     * @type string
     */
    private const string ROUTE_ESCAPE_REGEX = '`(?<=^|])[^]\[?]+?(?=\[|$)`';

    /**
     * The types to detect in a defined match "block"
     *
     * Examples of these blocks are as follows:
     *
     * - integer: '[i:id]'
     * - alphanumeric: '[a:username]'
     * - hexadecimal: '[h:color]'
     * - slug: '[s:article]'
     *
     * @var array<string, string>
     */
    protected const array match_types = [
        'i' => '[0-9]++',
        'a' => '[0-9A-Za-z]++',
        'h' => '[0-9A-Fa-f]++',
        's' => '[0-9A-Za-z-_]++',
        '*' => '.+?',
        '**' => '.++',
        '' => '[^/]+?'
    ];

    /**
     * Compiles a route object into a regular expression string to handle dynamic route matching.
     *
     * @param string $path
     * @return string The compiled regular expression representing the route.
     * @throws RegularExpressionCompilationException
     */
    public static function compileRouteRegexp(string $path): string
    {
        // Escape all literal segments (outside [...] parameter blocks) so regex metacharacters
        // in plain path text are treated as literals. This prevents accidental regex behavior
        // from characters like ., +, ?, (, ), etc. We do this first to keep parameter blocks intact.
        $route = preg_replace_callback(
            self::ROUTE_ESCAPE_REGEX,
            function ($match) {
                return preg_quote($match[0]);
            },
            $path
        );

        // Transform parameter blocks (e.g. [i:id], [:slug], [name] with optional suffix) into
        // proper PCRE named-capturing groups. Each match provides:
        // - $pre: any literal prefix before the parameter (like a leading slash)
        // - $type: a type token or raw regex (resolved via self::match_types if aliased)
        // - $param: the parameter name (maybe empty for unnamed groups)
        // - $optional: whether the whole segment is optional
        $route = preg_replace_callback(
            self::ROUTE_COMPILE_REGEX,
            function ($match) {
                [, $pre, $type, $param, $optional] = $match;

                // Map known type aliases (e.g. 'i' => '\d+') or use the raw type if not aliased.
                $type = self::match_types[$type] ?? $type;

                // Build a non-capturing group for the segment that contains an optional named
                // inner capture (?P<name>) followed by the resolved type pattern.
                // Older PCRE variants need the 'P' style for named groups.
                // Add ? after the group when the segment is optional.
                return sprintf(
                    '(?:%s(%s%s))%s',
                    $pre,
                    $param !== '' ? "?P<$param>" : '',
                    $type,
                    $optional ? '?' : ''
                );
            },
            $route
        );

        // Anchor the compiled route to match the entire path string.
        return "`^$route$`";
    }

    /**
     * Validate a regular expression
     *
     * This simply checks if the regular expression is able to be compiled
     * and converts any warnings or notices in the compilation to an exception
     *
     * @param string $regex The regular expression to validate
     *
     * @return void
     * @throws RegularExpressionCompilationException
     */
    public static function validateRegularExpression(string $regex): void
    {
        $error_string = null;

        // Set an error handler temporarily
        set_error_handler(
            function (int $errno, string $errStr) use (&$error_string): bool {
                $error_string = $errStr;
                return true;
            },
            E_NOTICE | E_WARNING
        );

        if (false === preg_match($regex, '') || !empty($error_string)) {
            // Remove our temporary error handler
            restore_error_handler();

            throw new RegularExpressionCompilationException(
                $error_string,
                preg_last_error()
            );
        }

        // Remove our temporary error handler
        restore_error_handler();
    }

    /**
     * Pre-process a path string
     *
     * This method wraps the path string in a regular expression syntax based
     * on whether the string is a catch-all or custom regular expression.
     * It also adds the namespace in a specific part, based on the style of expression
     *
     * @param string $namespace
     * @param string $path
     * @param bool $isNegated
     * @param bool $isCustomRegex
     * @param bool $isNegatedCustomRegex
     * @return string
     */
    public static function processPathString(
        string $namespace,
        string $path,
        bool $isNegated,
        bool $isCustomRegex,
        bool $isNegatedCustomRegex
    ): string {
        // Build a fully qualified path or regex from a namespace + path string.
        // Supports:
        // - Regex-prefixed paths starting with "@...".
        // - Negated regex with "!@...".
        // - Optional "^" at the start of the user-supplied pattern to avoid automatic ".*" prefixing.
        // - A special NULL_PATH_VALUE ("*") meaning "namespace root only".
        // The return value is either a regex string (starting with "@^") or a plain concatenated path.
        if ($namespace != '' && isset($path[0]) && ($isCustomRegex || $isNegatedCustomRegex)) {
            // Strip leading "@", or "!@" for negation.
            $path = substr($path, $isNegated ? 2 : 1);

            // If the regex begins with "^", keep it as-is; otherwise, allow any chars after the namespace by prefixing ".*".
            // This lets patterns like "@^/posts" anchor immediately after the namespace,
            // while "@/posts" becomes ".*\/posts" to match deeper subpaths.
            $path = ($path[0] == '^') ? substr($path, 1) : '.*' . $path;

            // Prepend namespace and anchor at the start.
            // For negation, wrap the pattern in a negative lookahead.
            // Examples:
            //  - Positive: "^api/.*users"
            //  - Negated:  "^api/(?!.*admin)"
            $path = $isNegated
                ? '^' . $namespace . '(?!' . $path . ')'
                : '^' . $namespace . $path;
        } elseif ($namespace != '' && $path == Route::NULL_PATH_VALUE) {
            // Special case: "*" means match only the namespace root (exact or with trailing slash).
            // Example: namespace "api" => "^api(/|$)"
            $path = '^' . $namespace . '(/|$)';
        } elseif ($isCustomRegex) {
            // Special case: "@" means "use the user-supplied regex as-is".
            $path = $namespace . substr($path, $isNegated ? 2 : 1);
        } else {
            // Default: plain concatenation (non-regex), e.g., "api" + "/users" => "api/users".
            $path = !$isNegated ? $namespace . $path : $namespace . ltrim($path, '!');
        }

        return $path;
    }

    /**
     * Generate a URL path for a named route.
     *
     * Looks up a route by name and reconstructs its path by reversing the route definition:
     * - Named placeholders (e.g. `[i:id]`, `[a:slug]`, `[h:hex]`, `[s:name]`) are replaced using values from $params.
     * - Optional segments are removed if a placeholder value is not provided.
     * - If no replacement occurs and the route was defined as a custom regex (starts with `@`), the result is:
     *   - "/" when $flatten_regex is true (default),
     *   - the original regex string when $flatten_regex is false.
     *
     * Note: Values in $params should be pre-encoded as needed (this method does not URL-encode).
     *
     * @param Route $route
     * @param array<string, string>|null $params Key-value map of placeholder names to substitute into the route.
     *                                                Missing values for optional placeholders remove their segment;
     *                                                missing values for required placeholders keep the original token.
     * @param bool $flatten_regex When true, flattens custom-regex routes (prefixed with "@") to "/" if no substitutions occur.
     *
     * @return string The generated path string for the named route.
     *
     */
    public static function getPathFor(Route $route, ?array $params = null, bool $flatten_regex = true): string
    {
        $path = $route->originalPath;

        // Use our compilation regex to reverse the path's compilation from its definition
        $reversed_path = preg_replace_callback(
            static::ROUTE_COMPILE_REGEX,
            function ($match) use ($params) {
                [$block, $pre, , $param, $optional] = $match;

                if (isset($params[$param])) {
                    return $pre . $params[$param];
                } elseif ($optional) {
                    return '';
                }

                return $block;
            },
            $path
        );

        // If the path and reversed_path are the same, the regex must have not matched/replaced
        if ($path === $reversed_path && $flatten_regex && ($route->isCustomRegex || $route->isNegatedCustomRegex)) {
            // If the path is a custom regular expression, and we're "flattening", just return a slash
            $path = '/';
        } else {
            $path = $reversed_path;
        }

        return $path;
    }

}