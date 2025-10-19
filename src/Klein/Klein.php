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

use Cache\Adapter\PHPArray\ArrayCachePool;
use InvalidArgumentException;
use Klein\DataCollection\RouteCollection;
use Klein\Exceptions\DispatchHaltedException;
use Klein\Exceptions\HttpException;
use Klein\Exceptions\HttpExceptionInterface;
use Klein\Exceptions\LockedResponseException;
use Klein\Exceptions\UnhandledException;
use Klein\Routes\Route;
use Klein\Routes\RouteFactory;
use OutOfBoundsException;
use SplQueue;
use SplStack;
use Throwable;

/**
 * Klein
 *
 * Main Klein router class
 */
class Klein
{

    /**
     * Class constants
     */

    /**
     * The regular expression used to compile and match URL's
     *
     * @type string
     */
    const string ROUTE_COMPILE_REGEX = '`(\\\?(?:/|\.|))\[([^:\]]*)(?::([^:\]]*))?](\?|)`';

    /**
     * Dispatch route output handling
     *
     * Don't capture anything. Behave as normal.
     *
     * @type int
     */
    const int DISPATCH_NO_CAPTURE = 0;

    /**
     * Dispatch route output handling
     *
     * Capture all output and return it from dispatch
     *
     * @type int
     */
    const int DISPATCH_CAPTURE_AND_RETURN = 1;

    /**
     * Dispatch route output handling
     *
     * Capture all output and replace the response body with it
     *
     * @type int
     */
    const int DISPATCH_CAPTURE_AND_REPLACE = 2;

    /**
     * Dispatch route output handling
     *
     * Capture all output and prepend it to the response body
     *
     * @type int
     */
    const int DISPATCH_CAPTURE_AND_PREPEND = 3;

    /**
     * Dispatch route output handling
     *
     * Capture all output and append it to the response body
     *
     * @type int
     */
    const int DISPATCH_CAPTURE_AND_APPEND = 4;


    /**
     * Class properties
     */

    /**
     * Collection of the routes to match on dispatch
     *
     * @type RouteCollection
     */
    protected RouteCollection $routes;

    /**
     * The Route factory object responsible for creating Route instances
     *
     * @type AbstractRouteFactory
     */
    protected AbstractRouteFactory $route_factory;

    /**
     * A stack of error callback callables
     *
     * @var SplStack<callable|string>
     */
    protected SplStack $error_callbacks;

    /**
     * A stack of HTTP error callback callables
     *
     * @var SplStack<callable>
     */
    protected SplStack $http_error_callbacks;

    /**
     * A queue of callbacks to call after processing the dispatch loop
     * and before the response is sent
     *
     * @var SplQueue<callable|string>
     */
    protected SplQueue $after_filter_callbacks;

    /**
     * The output buffer level used by the dispatch process
     *
     * @type int
     */
    private int $output_buffer_level;


    /**
     * Route objects
     */

    /**
     * The Request object passed to each matched route
     *
     * @type Request
     */
    protected Request $request;

    /**
     * The Response object passed to each matched route
     *
     * @type AbstractResponse
     */
    protected AbstractResponse $response;

    /**
     * The service provider object passed to each matched route
     *
     * @type ServiceProvider
     */
    protected ServiceProvider $service;

    /**
     * A generic variable passed to each matched route
     *
     * @type mixed
     */
    protected mixed $app;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * Create a new Klein instance with optionally injected dependencies
     * This DI allows for easy testing, object mocking, or class extension
     *
     * @param ServiceProvider|null $service Service provider object responsible for utilitarian behaviors
     * @param App|null $app An object passed to each route callback, defaults to an App instance
     * @param RouteCollection|null $routes Collection object responsible for containing all route instances
     * @param AbstractRouteFactory|null $routeFactory A factory class responsible for creating Route instances
     */
    public function __construct(
        ?ServiceProvider $service = null,
        ?App $app = null,
        ?RouteCollection $routes = null,
        ?AbstractRouteFactory $routeFactory = null,
    ) {
        // Instantiate and fall back to defaults
        $this->service = $service ?: new ServiceProvider();
        $this->app = $app ?: new App();
        $this->routes = $routes ?: new RouteCollection();
        $this->route_factory = $routeFactory ?: new RouteFactory('', new ArrayCachePool());

        $this->error_callbacks = new SplStack();
        $this->http_error_callbacks = new SplStack();
        $this->after_filter_callbacks = new SplQueue();
    }

    /**
     * Returns the route's collection object
     *
     * @return RouteCollection
     */
    public function routes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Returns the request object
     *
     * @return Request
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Returns the response object
     *
     * @return AbstractResponse
     */
    public function response(): AbstractResponse
    {
        return $this->response;
    }

    /**
     * Returns the service object
     *
     * @return ServiceProvider
     */
    public function service(): ServiceProvider
    {
        return $this->service;
    }

    /**
     * Returns the app object
     *
     * @return App
     */
    public function app(): App
    {
        return $this->app;
    }

    /**
     * Add a new route to be matched on dispatch
     *
     * Essentially, this method is a standard "Route" builder/factory,
     * allowing a loose argument format and a standard way of creating
     * Route instances
     *
     * This method takes its arguments in a very loose format
     * The only "required" parameter is the callback (which is very strange considering the argument definition order)
     *
     * <code>
     * $router = new Klein();
     *
     * $router->respond( function() {
     *     echo 'this works';
     * });
     * $router->respond('/endpoint', function() {
     *     echo 'this also works';
     * });
     * $router->respond('POST', '/endpoint', function() {
     *     echo 'this also works!!!!';
     * });
     * </code>
     *
     * @param string|string[]|null $method HTTP Method to match
     * @param string|null $path Route URI path to match
     * @param callable|null $callback Callable callback method to execute on route match
     *
     * @return Route
     */
    public function respond(string|array|null $method = null, ?string $path = '*', ?callable $callback = null): Route
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expected a callable. Got an uncallable ' . gettype($callback));
        }

        $route = $this->route_factory->build($callback, $path, $method);

        $this->routes->add($route);

        return $route;
    }

    /**
     * Collect a set of routes under a common namespace
     *
     * The routes may be passed in as either a callable (which holds the route definitions),
     * or as a string of a filename, of which to "include" under the Klein router scope
     *
     * <code>
     * $router = new Klein();
     *
     * $router->with('/users', function($router) {
     *     $router->respond('/', function() {
     *         // do something interesting
     *     });
     *     $router->respond('/[i:id]', function() {
     *         // do something different
     *     });
     * });
     *
     * $router->with('/cars', __DIR__ . '/routes/cars.php');
     * </code>
     *
     * @param string $namespace The namespace under which to collect the routes
     * @param callable|string $routes The defined routes callable or filename to collect under the namespace
     *
     * @return void
     */
    public function with(string $namespace, callable|string $routes): void
    {
        $previous = $this->route_factory->getNamespace();

        $this->route_factory->appendNamespace($namespace);

        if (is_callable($routes)) {
            call_user_func($routes, $this);
        } else {
            require $routes;
        }

        $this->route_factory->setNamespace($previous);
    }

    /**
     * Dispatch the request to the appropriate route(s)
     *
     * Dispatch with optionally injected dependencies
     * This DI allows for easy testing, object mocking, or class extension
     *
     * @param Request|null $request The request to give it to each callback
     * @param AbstractResponse|null $response The response object to give to each callback
     * @param boolean $send_response Whether to "send" the response after the last route has been matched
     * @param int $capture Specify a DISPATCH_* constant to change the output capturing behavior
     *
     * @return void|string
     * @throws Throwable
     */
    public function dispatch(
        ?Request $request = null,
        ?AbstractResponse $response = null,
        bool $send_response = true,
        int $capture = self::DISPATCH_NO_CAPTURE
    ) {
        // Set/Initialize our objects to be sent in each callback
        $this->request = $request ?: Request::createFromGlobals();
        $this->response = $response ?: new Response();

        // Access the current Request object, get its "named parameters" collection,
        // and replace its internal attributes with an empty array (i.e., clear/reset them).
        // paramsNamed() returns a DataCollection; replace([]) sets its attributes to [] and returns the same collection.
        $this->request->paramsNamed()->replace();

        // Bind our objects to our service
        $this->service->bind($this->request, $this->response);

        // Prepare any named routes
        $this->routes->prepareNamed();

        // Grab some data from the request
        $uri = $this->request->pathname();

        /** @var string $requestMethod */
        /** @type string $requestMethod */
        $requestMethod = $this->request->method();

        // Set up some variables for matching
        $skipRemaining = 0;
        $matched = $this->routes->cloneEmpty(); // Get a clone of the route's collection, as it may have been injected
        $matchedMethods = [];

        // Start output buffering
        ob_start();
        $this->output_buffer_level = ob_get_level();

        try {
            /** @var Route $route */
            foreach ($this->routes as $route) {
                // Are we skipping any matches?
                if ($skipRemaining > 0) {
                    $skipRemaining--;
                    continue;
                }

                // Determine if the current request's HTTP method matches what the route allows.
                // - matchesMethod(...) returns:
                //     true  => the route explicitly allows this method (incl. HEAD treated like GET)
                //     false => the route explicitly disallows this method
                //     null => the route did not specify any method (i.e., it accepts any method)
                // - Using `?? true` treats "no method specified" (null) as a match.
                // The result is stored in $possibleMatch.
                $possibleMatch = $this->matchesMethod($requestMethod, $route->method) ?? true;

                // Matches URI against the route path
                // Try to match the current route's path against the incoming URI.
                // Returns:
                // - matched: whether the regex/pattern matched
                // - negate: whether the route was negated (the path starts with '!')
                // - params: any captured named params from the path
                $pathMatchResult = $this->matchRoute($route, $uri);

                // Apply negation: effective match if (matched XOR negate) is true.
                if ($pathMatchResult['matched'] ^ $route->isNegated) {
                    // Route path matched; check if this route is a possible match (e.g., method too).
                    if ($possibleMatch) {
                        // If the pattern captured params, decode per RFC 3986 and merge into request.
                        if (!empty($pathMatchResult['params'])) {
                            // RFC 3986: decode percent-encoded octets without converting '+' to space.
                            $paramsNamed = $this->request->paramsNamed()->all();
                            foreach ($pathMatchResult['params'] as $key => $value) {
                                if (is_numeric($key)) {
                                    $paramsNamed[] = rawurldecode($value);
                                } else {
                                    $paramsNamed[$key] = rawurldecode($value);
                                }
                            }
                            $this->request->paramsNamed()->replace($paramsNamed);
                        }

                        try {
                            // Execute the route callback/middleware chain.
                            $this->handleRouteCallback($route, $matched, $matchedMethods);
                        } catch (DispatchHaltedException $e) {
                            // Control-flow exceptions to alter dispatch:
                            switch ($e->getCode()) {
                                case DispatchHaltedException::SKIP_THIS:
                                    // Skip this route and continue with the next one.
                                    continue 2;
                                case DispatchHaltedException::SKIP_NEXT:
                                    // Skip a number of further routes.
                                    $skipRemaining = $e->getNumberOfSkips();
                                    break;
                                case DispatchHaltedException::SKIP_REMAINING:
                                    // Stop processing any more routes.
                                    break 2;
                                default:
                                    // Unknown control code: rethrow.
                                    throw $e;
                            }
                        }

                        // Record this route as matched unless it's the catch-all '*'.
                        $route->countMatch && $matched->add($route);
                    }

                    // Accumulate HTTP methods that matched (for 405 Method Not Allowed reporting).
                    if ($route->countMatch) {
                        $matchedMethods = array_unique(
                            array_filter(
                                array_merge($matchedMethods, (array)$route->method)
                            )
                        );
                    }
                }
            }

            // Handle our 404/405 conditions
            if ($matched->isEmpty() && count($matchedMethods) > 0) {
                // Add our methods to our allowed headers
                $this->response->header('Allow', implode(', ', $matchedMethods));

                if ($requestMethod != HttpMethod::OPTIONS->name) {
                    throw HttpException::createFromCode(405);
                }
            } elseif ($matched->isEmpty()) {
                throw HttpException::createFromCode(404);
            }
        } catch (HttpExceptionInterface $e) {
            // Grab our original response lock state
            $locked = $this->response->isLocked();

            // Call our http error handlers
            $this->httpError($e, $matched, $matchedMethods);

            // Make sure we return our response to its original lock state
            if (!$locked) {
                $this->response->unlock();
            }
        } catch (Throwable $e) {
            $this->error($e);
        }

        try {
            // If the response is configured for chunked transfer-encoding, send the next chunk now
            if ($this->response->chunked) {
                $this->response->chunk();
            } else {
                // Apply a capture strategy (e.g., capture output to return/replace/append)
                // If the strategy decides to return content immediately, short-circuit here
                $captured = $this->handleCaptureStrategy($capture);
                if ($captured !== null) {
                    return $captured;
                }
                // Normalize unknown capture modes to "no capture"
                if (!in_array($capture, [
                    self::DISPATCH_CAPTURE_AND_RETURN,
                    self::DISPATCH_CAPTURE_AND_REPLACE,
                    self::DISPATCH_CAPTURE_AND_PREPEND,
                    self::DISPATCH_CAPTURE_AND_APPEND,
                ], true)) {
                    $capture = self::DISPATCH_NO_CAPTURE;
                }
            }

            // Special handling for HEAD requests: send headers only, no body content
            if ($requestMethod == HttpMethod::HEAD->name) {
                $this->response->body(''); // Ensure an empty body for HEAD
                // Discard any buffered output so nothing is sent
                $this->endBuffersToLevel($this->output_buffer_level, 'ob_end_clean');
            } elseif ($capture === self::DISPATCH_NO_CAPTURE) {
                // If not capturing output, flush any buffered output to the client
                $this->endBuffersToLevel($this->output_buffer_level, 'ob_end_flush');
            }
        } catch (LockedResponseException) {
            // Do nothing, since this is an automated behavior
        }

        // Run our after dispatch callbacks
        $this->callAfterDispatchCallbacks();

        if ($send_response && !$this->response->isSent()) {
            $this->response->send();
        }
    }

    /**
     * Handle the capture strategy based on the provided mode.
     *
     * This method processes output buffers according to the specified capture mode.
     * Depending on the mode, it can return the last drained output chunk, replace the response body,
     * prepend to the response body, or append to the response body. If the mode is unknown, no action is performed.
     *
     * @param int $captureMode The mode that determines how the captured output is handled.
     *                         Must match one of the defined constants such as DISPATCH_CAPTURE_AND_RETURN,
     *                         DISPATCH_CAPTURE_AND_REPLACE, DISPATCH_CAPTURE_AND_PREPEND, or DISPATCH_CAPTURE_AND_APPEND.
     *
     * @return ?string Returns the last drained chunk if the mode is DISPATCH_CAPTURE_AND_RETURN.
     *                 Otherwise, returns null.
     */
    private function handleCaptureStrategy(int $captureMode): ?string
    {
        // Handle different strategies for dealing with any content currently sitting in PHP's output buffers.
        // All cases use drainBuffersToLevel($this->output_buffer_level, $callback) to consume buffered chunks up to a target level,
        // and then decide what to do with each chunk (return it, replace, prepend, or append to the response).

        switch ($captureMode) {
            case self::DISPATCH_CAPTURE_AND_RETURN:
                // Collect the last drained chunk and return it to the caller.
                // Note: if multiple chunks are drained, only the most recent (last) one is returned.
                // If no output exists, null is returned.
                $lastCaptured = null;
                $this->drainBuffersToLevel(
                    $this->output_buffer_level,
                    function (string $chunk) use (&$lastCaptured): void {
                        // Overwrite on each chunk so the final value is the last drained piece.
                        $lastCaptured = $chunk;
                    }
                );
                return $lastCaptured;

            case self::DISPATCH_CAPTURE_AND_REPLACE:
                // Replace the entire response body with the drained output.
                // If multiple chunks are drained, the response body is set for each chunk in order,
                // resulting in the final chunk becoming the response body.
                $this->drainBuffersToLevel($this->output_buffer_level, function (string $chunk): void {
                    $this->response->body($chunk);
                });
                return null;

            case self::DISPATCH_CAPTURE_AND_PREPEND:
                // Prepend drained output to the beginning of the existing response body.
                // If multiple chunks are drained, each is prepended in the order they are drained,
                // which can affect final ordering depending on drain sequence.
                $this->drainBuffersToLevel($this->output_buffer_level, function (string $chunk): void {
                    $this->response->prepend($chunk);
                });
                return null;

            case self::DISPATCH_CAPTURE_AND_APPEND:
                // Append drained output to the end of the existing response body.
                // Multiple chunks will be appended sequentially in the order they are drained.
                $this->drainBuffersToLevel($this->output_buffer_level, function (string $chunk): void {
                    $this->response->append($chunk);
                });
                return null;

            default:
                // Unknown capture mode: do nothing and return null.
                return null;
        }
    }

    /**
     * Drain output buffers down to a specific level, applying a handler to each drained chunk.
     *
     * @param int $targetLevel
     * @param callable(string):void $onChunk
     */
    private function drainBuffersToLevel(int $targetLevel, callable $onChunk): void
    {
        while (ob_get_level() >= $targetLevel) {
            $content = ob_get_clean();
            if ($content === false) {
                break;
            }
            $onChunk($content);
        }
    }

    /**
     * Flush or clean output buffers down to a specific level using the provided endFn.
     *
     * @param int $targetLevel
     * @param callable():bool $endFn ob_end_flush or ob_end_clean
     */
    private function endBuffersToLevel(int $targetLevel, callable $endFn): void
    {
        while (ob_get_level() >= $targetLevel) {
            $endFn();
        }
    }

    /**
     * Determines if the incoming HTTP method matches the method(s) defined by a route.
     *
     * This method checks if the HTTP request method corresponds to the allowed method(s)
     * for the given route. It supports case-insensitive comparisons, handles multiple
     * allowed methods, and considers special cases like treating HEAD requests as
     * matching routes that declare either HEAD or GET.
     *
     * @param string $requestMethod The HTTP method of the incoming request.
     * @param mixed $routeMethod The HTTP method(s) defined by the route. Can be a string,
     *                            an array of strings, or null if the route does not restrict methods.
     *
     * @return ?bool Returns true if the method matches, false if the method does not match,
     *               or null if the route does not restrict methods.
     */
    private function matchesMethod(string $requestMethod, mixed $routeMethod): ?bool
    {
        // Determine if the incoming HTTP method matches the method(s) defined by a route.
        // Returns:
        // - true  => method matches
        // - false => method does not match
        // - null  => route did not specify any method (i.e., matches any method)

        // If the route defines multiple allowed methods (e.g., ['GET', 'POST'])
        if (is_array($routeMethod)) {
            foreach ($routeMethod as $candidate) {
                // Exact, case-insensitive match (e.g., GET === get)
                if ($candidate === $requestMethod) {
                    return true;
                }
                // Special-case: HTTP/1.1 allows servers to treat HEAD like GET without a body.
                // Consider HEAD matching routes that declare HEAD or GET.
                if ($requestMethod == HttpMethod::HEAD->name && ($candidate == HttpMethod::HEAD->name || $candidate == HttpMethod::GET->name)) {
                    return true;
                }
            }
            // None of the declared methods matched
            return false;
        }

        // Route did not specify a method: indicate "no constraint"
        if ($routeMethod === null) {
            return null;
        }

        // Single method declared: exact, case-insensitive match
        if ($requestMethod == $routeMethod) {
            return true;
        }

        // Allow HEAD requests to match routes that declare HEAD or GET
        if ($requestMethod == HttpMethod::HEAD->name && ($routeMethod == HttpMethod::HEAD->name || $routeMethod == HttpMethod::GET->name)) {
            return true;
        }

        // No match
        return false;
    }

    /**
     * Matches a given URI against the specified route and determines if it aligns with the route's pattern.
     *
     * @param Route $route The route object containing path and regex information used for matching.
     * @param string $uri The URI to be checked against the route's pattern.
     * @return array An associative array containing:
     *               - 'matched' (bool): Whether the URI matches the route.
     *               - 'params' (array): Extracted named parameters, if any.
     */
    private function matchRoute(Route $route, string $uri): array
    {
        // Fast path: wildcard route matches everything
        if ($route->path === '*') {
            return ['matched' => true, 'params' => []];
        }

        // Remove a leading slash from the incoming URI so comparisons are consistent
        $normalizedUri = ltrim($uri, '/');

        // If the route is static (no dynamic parameters/regex) and the normalized URI
        // exactly equals the route's path (also normalized and null-safe), we have a match.
        // Return early with "matched" and no params.
        if (!$route->isDynamic && $normalizedUri == ltrim($route->path ?? '', '/')) {
            return ['matched' => true, 'params' => []];
        }

        // If the route uses a custom regex, drop a leading start-anchor (^) from its pattern body;
        // otherwise use the raw route path. Null-safe for $route->path.
        $patternBody = $route->isCustomRegex ? ltrim($route->path ?? '', '^') : $route->path;

        // From the (slash-trimmed) pattern body, extract the literal prefix by splitting on the
        // first regex-significant character: [, (, ., ?, +, *, {.
        // Result is an array where index 0 is the plain literal prefix used for a fast prefix check.
        $literalPrefixParts = preg_split('`[\[(.?+*{]`', ltrim($patternBody, '/'), 2);

        // Fast prefix check: if the URI (without a leading slash) doesn't start with the
        // literal route prefix (also trimmed), we can fail early without compiling regex.
        if (!str_starts_with(ltrim($uri, '/'), rtrim($literalPrefixParts[0], '/'))) {
            return ['matched' => false, 'params' => []];
        }

        // Matches the compiled regex against the URI and returns any named parameters.
        $matched = (bool)preg_match($route->regex, $uri, $params);

        // Note: caller will apply XOR with $isNegated to produce the effective match result.
        return ['matched' => $matched, 'params' => $params];
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
     * @param string $route_name The route's registered name.
     * @param array<string, string>|null $params Key-value map of placeholder names to substitute into the route.
     *                                                Missing values for optional placeholders remove their segment;
     *                                                missing values for required placeholders keep the original token.
     * @param bool $flatten_regex When true, flattens custom-regex routes (prefixed with "@") to "/" if no substitutions occur.
     *
     * @return string The generated path string for the named route.
     *
     * @throws OutOfBoundsException If no route exists with the given name.
     */
    public function getPathFor(string $route_name, ?array $params = null, bool $flatten_regex = true): string
    {
        // First, grab the route
        /** @var Route $route */
        $route = $this->routes->get($route_name);

        // Make sure we are getting a valid route
        if (null === $route) {
            throw new OutOfBoundsException('No such route with name: ' . $route_name);
        }

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

    /**
     * Handle a route's callback
     *
     * This handles common exceptions and their output
     * to keep the "dispatch()" method DRY
     *
     * @param Route $route
     * @param RouteCollection $matched
     * @param string[] $methods_matched
     *
     * @return void
     */
    protected function handleRouteCallback(Route $route, RouteCollection $matched, array $methods_matched): void
    {
        // Handle the callback
        $returned = call_user_func(
            $route->callback, // Instead of relying on the slower "invoke" magic
            $this->request,
            $this->response,
            $this->service,
            $this->app,
            $this, // Pass the Klein instance
            $matched,
            $methods_matched
        );

        if ($returned instanceof AbstractResponse) {
            $this->response = $returned;
        } else {
            // Otherwise, attempt to append the returned data
            try {
                $buffer = (string)($returned ?? '');
                if ($buffer !== '') {
                    $this->response->append($buffer);
                }
            } catch (LockedResponseException) {
                // Do nothing, since this is an automated behavior
            }
        }
    }

    /**
     * Adds an error callback to the stack of error handlers
     *
     * @param callable|string $callback The callable function to execute in the error handling chain
     *
     * @return void
     */
    public function onError(callable|string $callback): void
    {
        $this->error_callbacks->push($callback);
    }

    /**
     * Routes an exception through the error callbacks
     *
     * @param Throwable $err The exception that occurred
     *
     * @return void
     * @throws UnhandledException      If the error/exception isn't handled by an error callback
     * @throws Throwable
     */
    protected function error(Throwable $err): void
    {
        $type = get_class($err);
        $msg = $err->getMessage();

        try {
            if (!$this->error_callbacks->isEmpty()) {
                foreach ($this->error_callbacks as $callback) {
                    if (is_callable($callback)) {
                        call_user_func($callback, $this, $msg, $type, $err);

                        return;
                    }

                    $this->service->flash($err);
                    $this->response->redirect($callback);
                }
            } else {
                $this->response->code(500);

                while (ob_get_level() >= $this->output_buffer_level) {
                    ob_end_clean();
                }

                throw new UnhandledException($msg, $err->getCode(), $err);
            }
        } catch (Throwable $e) {
            // Make sure to clean the output buffer before bailing
            while (ob_get_level() >= $this->output_buffer_level) {
                ob_end_clean();
            }

            throw $e;
        }

        // Lock our response, since we probably don't want
        // anything else messing with our error code/body
        $this->response->lock();
    }

    /**
     * Adds an HTTP error callback to the stack of HTTP error handlers
     *
     * @param callable $callback The callable function to execute in the error handling chain
     *
     * @return void
     */
    public function onHttpError(callable $callback): void
    {
        $this->http_error_callbacks->push($callback);
    }

    /**
     * Handles an HTTP error exception through our HTTP error callbacks
     *
     * @param HttpExceptionInterface $http_exception The exception that occurred
     * @param RouteCollection $matched The collection of routes that were matched in dispatch
     * @param string[] $methods_matched The HTTP methods that were matched in dispatch
     *
     * @return void
     */
    protected function httpError(
        HttpExceptionInterface $http_exception,
        RouteCollection $matched,
        array $methods_matched
    ): void {
        if (!$this->response->isLocked()) {
            $this->response->code($http_exception->getCode());
        }

        if (!$this->http_error_callbacks->isEmpty()) {
            foreach ($this->http_error_callbacks as $callback) {
                if ($callback instanceof Route) {
                    $this->handleRouteCallback($callback, $matched, $methods_matched);
                } elseif (is_callable($callback)) {
                    call_user_func(
                        $callback,
                        $http_exception->getCode(),
                        $this,
                        $matched,
                        $methods_matched,
                        $http_exception
                    );
                }
            }
        }

        // Lock our response, since we probably don't want
        // anything else messing with our error code/body
        $this->response->lock();
    }

    /**
     * Adds a callback to the stack of handlers to run after the dispatch
     * loop has handled all of the route callbacks and before the response
     * is sent
     *
     * @param callable $callback The callable function to execute in the after route chain
     *
     * @return void
     */
    public function afterDispatch(callable $callback): void
    {
        $this->after_filter_callbacks->enqueue($callback);
    }

    /**
     * Runs through and executes the after dispatch callbacks
     *
     * @return void
     * @throws Throwable
     */
    protected function callAfterDispatchCallbacks(): void
    {
        try {
            foreach ($this->after_filter_callbacks as $callback) {
                if (is_callable($callback)) {
                    call_user_func($callback, $this);
                }
            }
        } catch (Throwable $e) {
            $this->error($e);
        }
    }

    /**
     * Method aliases
     */

    /**
     * Quick alias to skip the current callback/route method from executing
     *
     * @return void
     * @throws DispatchHaltedException To halt/skip the current dispatch loop
     */
    public function skipThis(): void
    {
        throw new DispatchHaltedException('', DispatchHaltedException::SKIP_THIS);
    }

    /**
     * Quick alias to skip the next callback/route method from executing
     *
     * @param int $num The number of next matches to skip
     *
     * @return void
     * @throws DispatchHaltedException To halt/skip the current dispatch loop
     */
    public function skipNext(int $num = 1): void
    {
        $skip = new DispatchHaltedException('', DispatchHaltedException::SKIP_NEXT);
        $skip->setNumberOfSkips($num);

        throw $skip;
    }

    /**
     * Quick alias to stop the remaining callbacks/route methods from executing
     *
     * @return void
     * @throws DispatchHaltedException To halt/skip the current dispatch loop
     */
    public function skipRemaining(): void
    {
        throw new DispatchHaltedException('', DispatchHaltedException::SKIP_REMAINING);
    }

    /**
     * Alias to set a response code, lock the response, and halt the route matching/dispatching
     *
     * @param int|null $code Optional HTTP status code to send
     *
     * @return void
     * @throws DispatchHaltedException To halt/skip the current dispatch loop
     */
    public function abort(?int $code = null): void
    {
        if (null !== $code) {
            throw HttpException::createFromCode($code);
        }

        throw new DispatchHaltedException();
    }

    /**
     * OPTIONS alias for "respond()"
     *
     * @param string $path
     * @param callable|null $callback
     *
     * @return Route
     * @see Klein::respond()
     */
    public function options(string $path = '*', ?callable $callback = null): Route
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expected a callable. Got an uncallable ' . gettype($callback));
        }

        return $this->respond(HttpMethod::OPTIONS->name, $path, $callback);
    }

    /**
     * HEAD alias for "respond()"
     *
     * @param string $path
     * @param callable|null $callback
     *
     * @return Route
     * @see Klein::respond()
     */
    public function head(string $path = '*', ?callable $callback = null): Route
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expected a callable. Got an uncallable ' . gettype($callback));
        }

        return $this->respond(HttpMethod::HEAD->name, $path, $callback);
    }

    /**
     * GET alias for "respond()"
     *
     * @param string $path
     * @param callable|null $callback
     *
     * @return Route
     * @see Klein::respond()
     */
    public function get(string $path = '*', ?callable $callback = null): Route
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expected a callable. Got an uncallable ' . gettype($callback));
        }

        return $this->respond(HttpMethod::GET->name, $path, $callback);
    }

    /**
     * POST alias for "respond()"
     *
     * @param string $path
     * @param callable|null $callback
     *
     * @return Route
     * @see Klein::respond()
     */
    public function post(string $path = '*', ?callable $callback = null): Route
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expected a callable. Got an uncallable ' . gettype($callback));
        }

        return $this->respond(HttpMethod::POST->name, $path, $callback);
    }

    /**
     * PUT alias for "respond()"
     *
     * @param string $path
     * @param callable|null $callback
     *
     * @return Route
     * @see Klein::respond()
     */
    public function put(string $path = '*', ?callable $callback = null): Route
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expected a callable. Got an uncallable ' . gettype($callback));
        }

        return $this->respond(HttpMethod::PUT->name, $path, $callback);
    }

    /**
     * DELETE alias for "respond()"
     *
     * @param string $path
     * @param callable|null $callback
     *
     * @return Route
     * @see Klein::respond()
     */
    public function delete(string $path = '*', ?callable $callback = null): Route
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expected a callable. Got an uncallable ' . gettype($callback));
        }

        return $this->respond(HttpMethod::DELETE->name, $path, $callback);
    }

    /**
     * PATCH alias for "respond()"
     *
     * PATCH was added to HTTP/1.1 in RFC5789
     *
     * @link http://tools.ietf.org/html/rfc5789
     * @see  Klein::respond()
     *
     * @param string $path
     * @param callable|null $callback
     *
     * @return Route
     */
    public function patch(string $path = '*', ?callable $callback = null): Route
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expected a callable. Got an uncallable ' . gettype($callback));
        }

        return $this->respond(HttpMethod::PATCH->name, $path, $callback);
    }
}
