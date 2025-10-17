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

/**
 * ResponseCookie
 *
 * Class to represent an HTTP response cookie
 */
class ResponseCookie
{

    /**
     * Class properties
     */

    /**
     * The name of the cookie
     *
     * @type string
     */
    protected string $name;

    /**
     * The string "value" of the cookie
     *
     * @type string
     */
    protected string $value = '';

    /**
     * The date/time that the cookie should expire
     *
     * Represented by a Unix "Timestamp"
     *
     * @type int
     */
    protected int $expire = 0;

    /**
     * The path on the server that the cookie will
     * be available on
     *
     * @type string
     */
    protected string $path;

    /**
     * The domain that the cookie is available to
     *
     * @type string
     */
    protected string $domain;

    /**
     * Whether the cookie should only be transferred
     * over an HTTPS connection or not
     *
     * @type boolean
     */
    protected bool $secure;

    /**
     * Whether the cookie will be available through HTTP
     * only (not available to be accessed through
     * client-side scripting languages like JavaScript)
     *
     * @type boolean
     */
    protected bool $http_only;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @param string $name The name of the cookie
     * @param string|null $value The value to set the cookie with
     * @param int|null $expire The time that the cookie should expire
     * @param string|null $path The path of which to restrict the cookie
     * @param string|null $domain The domain of which to restrict the cookie
     * @param boolean $secure Flag of whether the cookie should only be sent over a HTTPS connection
     * @param boolean $http_only Flag of whether the cookie should only be accessible over the HTTP protocol
     */
    public function __construct(
        string $name,
        ?string $value = null,
        ?int $expire = null,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = false,
        bool $http_only = false
    ) {
        // Initialize our properties
        $this->setName($name);
        $this->setValue($value ?? '');
        $this->setExpire($expire ?? 0);
        $this->setPath($path ?? '');
        $this->setDomain($domain ?? '');
        $this->setSecure($secure ?? false);
        $this->setHttpOnly($http_only);
    }

    /**
     * Gets the cookie's name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the cookie's name
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the cookie's value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the cookie's value
     *
     * @param string $value
     *
     * @return static
     */
    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets the cookie's expire time
     *
     * @return int
     */
    public function getExpire(): int
    {
        return $this->expire;
    }

    /**
     * Sets the cookie's expire time
     *
     * The time should be an integer
     * representing a Unix timestamp
     *
     * @param int $expire
     *
     * @return static
     */
    public function setExpire(int $expire): static
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Gets the cookie's path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Sets the cookie's path
     *
     * @param string $path
     *
     * @return static
     */
    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Gets the cookie's domain
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Sets the cookie's domain
     *
     * @param string $domain
     *
     * @return static
     */
    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Gets the cookie's secure only flag
     *
     * @return boolean
     */
    public function getSecure(): bool
    {
        return $this->secure;
    }

    /**
     * Sets the cookie's secure only flag
     *
     * @param boolean $secure
     *
     * @return static
     */
    public function setSecure(bool $secure): static
    {
        $this->secure = $secure;

        return $this;
    }

    /**
     * Gets the cookie's HTTP only flag
     *
     * @return boolean
     */
    public function getHttpOnly(): bool
    {
        return $this->http_only;
    }

    /**
     * Sets the cookie's HTTP only flag
     *
     * @param boolean $http_only
     *
     * @return static
     */
    public function setHttpOnly(bool $http_only): static
    {
        $this->http_only = $http_only;

        return $this;
    }
}
