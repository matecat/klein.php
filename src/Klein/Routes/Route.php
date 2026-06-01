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

declare(strict_types=1);

namespace Klein\Routes;

use InvalidArgumentException;
use RuntimeException;
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
     * @var string
     */
    private string $processedPath;

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
     * @type string
     */
    private string $regex;
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
    public const string NULL_PATH_VALUE = '*';
    /**
     * Indicates whether a custom regular expression is used
     *
     * @type boolean
     */
    public readonly bool $isCustomRegex;
    /**
     * Indicates if the custom regular expression is negated
     *
     * @type boolean
     */
    public readonly bool $isNegatedCustomRegex;

    /**
     * Indicates whether the route is dynamic (contains parameters)
     *
     * @type boolean
     */
    public readonly bool $isDynamic;

    /**
     * A regular expression pattern used for matching parameters
     *
     * @var array<string,string[]>
     */
    private array $regexMatchingParams = [];

    /**
     * Indicates whether the route is matched
     * @var array<string, static>
     */
    private array $routeMatched = [];

    /**
     * @var string
     */
    public readonly string $hash;

    /**
     * Constructor
     *
     * @param callable $callback
     * @param string|null $path
     * @param string|string[]|null $method
     * @param string|null $namespace
     * @param bool|null $count_match
     * @param string|null $name
     * @throws InvalidArgumentException If the provided HTTP method is not valid
     */
    public function __construct(
        callable $callback,
        ?string $path = '*',
        string|array|null $method = null,
        ?string $namespace = '',
        ?bool $count_match = true,
        ?string $name = null
    ) {
        // Initialize some properties (do not use setter and getter, for fast access use public readonly properties)
        $this->hash = hrtime(true) . '.' . spl_object_hash($this);
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
        // Allow null, otherwise expect an array or a string
        $this->method = $this->validateMethod($method);

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

        $this->isDynamic =
            !$this->isCustomRegex &&
            (
                str_contains($path ?? '', '[') ||
                str_contains($path ?? '', '?') ||
                str_contains($this->namespace, '[') ||
                str_contains($this->namespace, '?')
            );

        // Normalize/compile the incoming path into a fully qualified path or regex,
        // based on the current namespace and special syntaxes (e.g., "@regex", "!@negated-regex", or NULL_PATH_VALUE).
        $this->processedPath = RouteRegexCompiler::processPathString(
            $this->namespace,
            $path ?? self::NULL_PATH_VALUE,
            $this->isNegated,
            $this->isCustomRegex,
            $this->isNegatedCustomRegex
        );

        $this->path = ltrim($this->processedPath, '^/');
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
     * @type boolean
     */
    private bool $isCompiled = false;

    /**
     * Compiles the route path into a regular expression and caches it.
     *
     * @return string Returns the compiled regex.
     * @throws RoutePathCompilationException If the route path cannot be compiled
     * @throws RuntimeException
     */
    public function getCompiledRegex(): string
    {
        if ($this->isCompiled) {
            return $this->regex;
        }

        // Build the regex if it wasn't cached.
        $this->regex = $this->compileRegexp();
        $this->isCompiled = true;
        return $this->regex;
    }

    /**
     * Sets the route match status against a specified URI and stores the captured parameters.
     *
     * This method attempts to record the parameters captured by the route's regex and marks the URI
     * as matched. The `regexMatchingParams` property is readonly and can only be set once per instance.
     * Later attempts to reset this property are silently ignored, ensuring the initial match remains.
     *
     * @param string[] $regexMatchingParams The parameters captured by the route's regex.
     * @param string $uri The URI that matches the route.
     * @return static Returns the current instance for method chaining.
     */
    public function setRouteMatchedAgainstUri(array $regexMatchingParams, string $uri): static
    {
        // Combine the hash and the URI to form a unique key for the route.
        // We could do a method for this, but this is a faster way to do it.
        $hashPerUri = $this->hash . '|' . $uri;

        // Attempt to record the params captured by the route's regex and mark this URI as matched.
        $this->regexMatchingParams[$hashPerUri] = $regexMatchingParams;
        $this->routeMatched[$hashPerUri] = $this;

        return $this; // allow chaining
    }

    /**
     * @return string[]
     */
    public function getRegexMatchingParams(string $uri): array
    {
        return $this->regexMatchingParams[$this->hash . '|' . $uri] ?? [];
    }

    /**
     * Retrieves the matched route information.
     *
     * Returns an array containing details about the matched route,
     * typically including relevant route parameters and metadata.
     *
     * @param string $uri
     * @return bool
     */
    public function routeMatchedAgainstUri(string $uri): bool
    {
        return (bool)($this->routeMatched[$this->hash . '|' . $uri] ?? false);
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
     * Validates and normalizes HTTP method(s) using the HttpMethod enum.
     *
     * @param string|string[]|null $method The HTTP method(s) to validate and normalize.
     * @return string|string[]|null Uppercase validated method(s), or null if unconstrained.
     *
     * @throws InvalidArgumentException If any method string is not a valid HTTP method.
     */
    private function validateMethod(string|array|null $method): string|array|null
    {
        if ($method === null) {
            return null;
        }

        // Single method string — one enum lookup, zero allocations
        if (is_string($method)) {
            return (HttpMethod::tryFrom(strtoupper($method))
                ?? throw new InvalidArgumentException("Invalid HTTP method: $method"))->value;
        }

        // Array of methods — validate each element via the enum
        $out = [];
        /** @var string|mixed $m */
        foreach ($method as $m) {
            if (!is_string($m)) {
                throw new InvalidArgumentException('Invalid HTTP method array structure');
            }
            $upper = strtoupper($m);
            $out[] = (HttpMethod::tryFrom($upper)
                ?? throw new InvalidArgumentException("Invalid HTTP method: $m"))->value;
        }

        return $out;
    }

    /**
     * Compiles the route path into a regular expression.
     *
     * Converts a user-defined route path into a regex pattern. If the route path
     * is already a custom regex, it wraps the path with PCRE delimiters. Otherwise,
     * it compiles the path to a fully anchored regex. Additionally, the method
     * validates that the resulting regex is properly formatted and can be compiled
     * by the PCRE engine. Throws an exception if the validation fails.
     *
     * @return string
     * @throws RoutePathCompilationException If the route path cannot be compiled
     * @throws RuntimeException
     */
    private function compileRegexp(): string
    {
        // Build the regex if it wasn't cached.
        // If the path is already a user-supplied regex, just wrap it with backticks as PCRE delimiters.
        if ($this->isCustomRegex) {
            $regex = '`' . $this->processedPath . '`';
        } else {
            // Otherwise, compile a human-readable route (with parameters) into a fully anchored regex.
            // Example result: `^/users/(?P<id>\d+)$`
            $regex = RouteRegexCompiler::compileRouteRegexp($this->processedPath, $this->isDynamic);

            try {
                // Validate the resulting regex compiles in PCRE.
                RouteRegexCompiler::validateRegularExpression($regex);
            } catch (RegularExpressionCompilationException $e) {
                // Re-throw as a route-specific exception with context.
                throw RoutePathCompilationException::createFromRoute($this, $e);
            }
        }

        return $regex;
    }
}
