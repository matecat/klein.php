<?php
/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author          Chris O'Hara <cohara87@gmail.com>
 * @author          Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link            https://github.com/klein/klein.php
 * @license         MIT
 */

namespace Klein\Routes;

use InvalidArgumentException;
use Klein\Exceptions\RegularExpressionCompilationException;
use Klein\Exceptions\RoutePathCompilationException;
use Klein\HttpMethod;

/**
 * Route
 *
 * Class to represent a route definition
 */
class Route
{

    /**
     * The namespace associated with the given context or functionality
     *
     * @type string
     */
    public readonly string $namespace;

    /**
     * The callback method to execute when the route is matched
     *
     * Any valid "callable" type is allowed
     *
     * @link http://php.net/manual/en/language.types.callable.php
     * @type callable
     */
    public readonly mixed $callback;

    /**
     * The URL path to match
     *
     * Allows for regular expression matching and/or basic string matching
     *
     * Examples:
     * - '/posts'
     * - '/posts/[:post_slug]'
     * - '/posts/[i:id]'
     *
     * @type string
     */
    public readonly string $path;

    /**
     * The original path of the route before any modification or processing
     *
     * @type string
     */
    public readonly string $originalPath;

    /**
     * The HTTP method to match
     *
     * May either be represented as a string or an array containing multiple methods to match
     *
     * Examples:
     * - 'POST'
     * - array('GET', 'POST')
     *
     * @var string|string[]|null
     */
    public readonly string|array|null $method;

    /**
     * Whether to count this route as a match when counting total matches
     *
     * @type boolean
     */
    public readonly bool $countMatch;

    /**
     * The name of the route
     *
     * Mostly used for reverse routing
     *
     * @type ?string
     */
    protected ?string $name = null;

    /**
     * The regular expression pattern used for matching
     *
     * @type ?string
     */
    public readonly ?string $regex;
    /**
     * Indicates if the condition is negated
     *
     * @type boolean
     */
    public readonly bool $isNegated;

    /**
     * The value given to paths when they are entered as null values
     *
     * @type string
     */
    const string NULL_PATH_VALUE = '*';
    /**
     * Indicates whether a custom regular expression is used
     *
     * @type boolean
     */
    public readonly bool $isCustomRegex;
    public readonly bool $isNegatedCustomRegex;

    /**
     * Constructor
     *
     * @param callable $callback
     * @param string|null $path
     * @param string|string[]|null $method
     * @param string|null $namespace
     * @param bool|null $count_match
     * @param string|null $name
     */
    public function __construct(
        callable $callback,
        ?string $path = '*',
        string|array|null $method = null,
        ?string $namespace = '',
        ?bool $count_match = true,
        ?string $name = null
    ) {
        // Initialize some properties (do not use setter, for fast access use public readonly properties)
        $this->callback = $callback;
        $this->namespace = $namespace ?? '';
        $this->name = $name;
        $this->originalPath = $path ?? '';

        // Determine whether there is a "countable" match condition for this route.
        // Prefer an explicitly provided $count_match flag if set;
        // otherwise, consider the given $path (or a NULL_PATH_VALUE fallback) and
        // set countMatch to true only when it is not the sentinel NULL_PATH_VALUE.
        $this->countMatch = $count_match ?? ($path ?? self::NULL_PATH_VALUE) !== static::NULL_PATH_VALUE;

        // If a single HTTP method string was provided (e.g., 'get' or 'POST'),
        // normalize and validate it against the HttpMethod enum (returns the enum name like 'GET').
        // Throws InvalidArgumentException if it's not a valid method.
        if (is_string($method)) {
            $method = $this->validateMethod($method);

            // If an array of methods was provided, validate each entry similarly,
            // returning a normalized array of valid method names.
        } elseif (is_array($method)) {
            $method = $this->validateMethodsArray($method);
        }

        // Allow null, otherwise expect an array or a string
        // Store the HTTP method (e.g., 'GET', 'POST') for this route/definition.
        $this->method = $method;

        // Peek at the first two characters of the compiled path string, if present.
        // These are used to detect negation "!" and custom-regex "@" prefixes.
        $first = $path[0] ?? null;
        $second = $path[1] ?? null;

        // Flag if the path is a negated pattern (starts with "!").
        $this->isNegated = $first == '!';

        // Set true when the route path is a negated custom-regex pattern, i.e. it starts with "!@"
        // - $this->isNegated is true if the first character is "!"
        // - $second == '@' confirms the custom-regex marker follows the negation
        $this->isNegatedCustomRegex = $this->isNegated && $second == '@';

        // Custom regex if it starts with "@" or is a negated custom regex ("!@")
        $this->isCustomRegex = ($first === '@') || $this->isNegatedCustomRegex;

        // Normalize/compile the incoming path into a fully qualified path or regex,
        // based on the current namespace and special syntaxes (e.g., "@regex", "!@negated-regex", or NULL_PATH_VALUE).
        $this->path = RouteCompiler::processPathString(
            $this->namespace,
            $path ?? self::NULL_PATH_VALUE,
            $this->isNegated,
            $this->isCustomRegex,
            $this->isNegatedCustomRegex
        );

        // Decide how to build the regular expression used to match this route.
        // If the path is already a custom regex, wrap it in backticks (PCRE delimiter) as-is.
        if ($this->isCustomRegex) {
            $this->regex = '`' . $this->path . '`';
        } else {

            // Otherwise, compile the human-friendly route path into a full anchored regex.
            // RouteCompiler::compileRouteRegexp() escapes literals, expands parameter blocks,
            // validates, and returns something like `^/users/(?P<id>\d+)$` with backtick delimiters.
            $this->regex = RouteCompiler::compileRouteRegexp($this->path);

            try {
                // Ensure the produced regex compiles; throw a descriptive exception if not.
                RouteCompiler::validateRegularExpression($this->regex);
            } catch (RegularExpressionCompilationException $e) {
                // Normalize regex compilation errors into a route-specific exception.
                throw RoutePathCompilationException::createFromRoute($this, $e);
            }
        }

        //TODO
        // Compile and cache the route regex:
        // - Use a simple APC cache if available to avoid recompiling hot routes.
        // - Use PSR-6 cache if available to avoid recompiling hot routes.
        // - Use a simple in-memory cache if not available.
//        $cacheKey = "route:" . $patternExpression;
//        $compiledRegex = $this->fetchRegexFromCache($cacheKey, $apcAvailable);
//
//        // On cache miss, compile and store
//        if ($compiledRegex === false) {
//            $compiledRegex = $this->compileRouteRegexp($patternExpression);
//            $this->storeRegexInCache($cacheKey, $compiledRegex, $apcAvailable);
//        }


    }

    /**
     * Get the name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * @param ?string $name
     *
     * @return static
     */
    public function setName(?string $name = null): static
    {
        $this->name = $name;

        return $this;
    }


    /**
     * Magic "__invoke" method
     *
     * The __invoke() method is called when a script tries to call an object as a function.
     *
     * Allows the ability to arbitrarily call this instance like a function.
     * All arguments are passed to the callback method
     *
     * @return mixed
     */
    public function __invoke(): mixed
    {
        return call_user_func_array(
            $this->callback,
            func_get_args()
        );
    }

    /**
     * Validate the given HTTP method
     *
     * @param string $method The HTTP method to validate
     *
     * @return string
     */
    protected function validateMethod(string $method): string
    {
        return HttpMethod::tryFrom(strtoupper($method))->name ?? throw new InvalidArgumentException(
            "Invalid HTTP method: $method"
        );
    }

    /**
     * Validate an array of methods
     *
     * @param string[] $methods Array of method names to validate
     *
     * @return string[]
     * @throws InvalidArgumentException If any of the methods in the array is invalid
     */
    protected function validateMethodsArray(array $methods): array
    {
        $uniformed_methods = [];
        foreach ($methods as $method) {
            $uniformed_methods[] = $this->validateMethod($method);
        }
        return $uniformed_methods;
    }

}
