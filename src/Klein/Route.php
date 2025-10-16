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

namespace Klein;

use InvalidArgumentException;

/**
 * Route
 *
 * Class to represent a route definition
 */
class Route
{

    /**
     * Properties
     */

    /**
     * The callback method to execute when the route is matched
     *
     * Any valid "callable" type is allowed
     *
     * @link http://php.net/manual/en/language.types.callable.php
     * @type callable
     */
    protected mixed $callback = null;

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
    protected string $path;

    /**
     * The HTTP method to match
     *
     * May either be represented as a string or an array containing multiple methods to match
     *
     * Examples:
     * - 'POST'
     * - array('GET', 'POST')
     *
     * @type string|array|null
     */
    protected string|array|null $method = null;

    /**
     * Whether to count this route as a match when counting total matches
     *
     * @type boolean
     */
    protected bool $count_match = true;

    /**
     * The name of the route
     *
     * Mostly used for reverse routing
     *
     * @type ?string
     */
    protected ?string $name = null;

    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @param callable $callback
     * @param string|null $path
     * @param string|array|null $method
     * @param boolean $count_match
     * @param null $name
     */
    public function __construct(
        callable $callback,
        ?string $path = '',
        string|array|null $method = null,
        bool $count_match = true,
        $name = null
    ) {
        // Initialize some properties (use our setters so we can validate param types)
        $this->setCallback($callback);
        $this->setPath($path);
        $this->setMethod($method);
        $this->setCountMatch($count_match);
        $this->setName($name);
    }

    /**
     * Get the callback
     *
     * @return callable|null
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * Set the callback
     *
     * @param callable $callback
     *
     * @return Route
     * @throws InvalidArgumentException If the callback isn't a callable
     */
    public function setCallback(callable $callback): static
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get the path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the path
     *
     * @param string $path
     *
     * @return Route
     */
    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the method
     *
     * @return array|string|null
     */
    public function getMethod(): array|string|null
    {
        return $this->method;
    }

    /**
     * Set the method
     *
     * @param string|array|null $method
     *
     * @return Route
     * @throws InvalidArgumentException If a non-string or non-array type is passed
     */
    public function setMethod(string|array|null $method): static
    {
        if (is_string($method)) {
            $this->validateMethod($method);
        } elseif (is_array($method)) {
            $this->validateMethodsArray($method);
        }

        // Allow null, otherwise expect an array or a string
        $this->method = $method;

        return $this;
    }

    /**
     * Get the count_match
     *
     * @return boolean
     */
    public function getCountMatch(): bool
    {
        return $this->count_match;
    }

    /**
     * Set the count_match
     *
     * @param boolean $count_match
     *
     * @return Route
     */
    public function setCountMatch(bool $count_match): static
    {
        $this->count_match = $count_match;

        return $this;
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
     * @return Route
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
        $args = func_get_args();

        return call_user_func_array(
            $this->callback,
            $args
        );
    }

    protected function validateMethod(string $method): void
    {
            HttpMethod::tryFrom(strtoupper($method)) ?? throw new InvalidArgumentException(
            "Invalid HTTP method: $method"
        );
    }

    protected function validateMethodsArray(array $methods): void
    {
        foreach ($methods as $method) {
            $this->validateMethod($method);
        }
    }

}
