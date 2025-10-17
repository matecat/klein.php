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

use Klein\DataCollection\HeaderDataCollection;
use Klein\DataCollection\ResponseCookieDataCollection;
use Klein\Exceptions\LockedResponseException;
use Klein\Exceptions\ResponseAlreadySentException;

/**
 * AbstractResponse
 */
abstract class AbstractResponse
{

    /**
     * Properties
     */

    /**
     * The HTTP version of the response
     *
     * @type string
     */
    protected string $protocol_version = '1.1';

    /**
     * The response body
     *
     * @type string
     */
    protected string $body = '';

    /**
     * HTTP response status
     *
     * @type HttpStatus
     */
    protected HttpStatus $status;

    /**
     * HTTP response headers
     *
     * @type HeaderDataCollection
     */
    protected HeaderDataCollection $headers;

    /**
     * HTTP response cookies
     *
     * @type ResponseCookieDataCollection
     */
    protected ResponseCookieDataCollection $cookies;

    /**
     * Whether the response is "locked" from
     * any further modification
     *
     * @type boolean
     */
    protected bool $locked = false;

    /**
     * Whether the response has been sent
     *
     * @type boolean
     */
    protected bool $sent = false;

    /**
     * Whether the response has been chunked or not
     *
     * @type boolean
     */
    public bool $chunked = false;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * Create a new AbstractResponse object with a dependency injected Headers instance
     *
     * @param ?string $body The response body's content
     * @param int $status_code The status code
     * @param array<string, string> $headers The response header "hash"
     */
    public function __construct(?string $body = null, int $status_code = 200, array $headers = [])
    {
        // Set our body and code using our internal methods
        $this->body($body ?? '');
        $this->code($status_code);

        $this->headers = new HeaderDataCollection($headers);
        $this->cookies = new ResponseCookieDataCollection();
    }

    /**
     * Get (or set) the HTTP protocol version
     *
     * Simply calling this method without any arguments returns the current protocol version.
     * Calling with an integer argument, however, attempts to set the protocol version to what
     * was provided by the argument.
     *
     * @param string|null $protocol_version
     *
     * @return string|AbstractResponse
     */
    public function protocolVersion(string $protocol_version = null): AbstractResponse|string|static
    {
        if (null !== $protocol_version) {
            // Require that the response be unlocked before changing it
            $this->requireUnlocked();

            $this->protocol_version = $protocol_version;

            return $this;
        }

        return $this->protocol_version;
    }

    /**
     * Get (or set) the response's body content
     *
     * Simply calling this method without any arguments returns the current response body.
     * Calling with an argument, however, sets the response body to what was provided by the argument.
     *
     * @param string|null $body The body content string
     *
     * @return string|AbstractResponse
     */
    public function body(string $body = null): AbstractResponse|string|static
    {
        if (null !== $body) {
            // Require that the response be unlocked before changing it
            $this->requireUnlocked();

            $this->body = $body;

            return $this;
        }

        return $this->body;
    }

    /**
     * Returns the status object
     *
     * @return HttpStatus
     */
    public function status(): HttpStatus
    {
        return $this->status;
    }

    /**
     * Returns the header's collection
     *
     * @return HeaderDataCollection
     */
    public function headers(): HeaderDataCollection
    {
        return $this->headers;
    }

    /**
     * Returns the cookie's collection
     *
     * @return ResponseCookieDataCollection
     */
    public function cookies(): ResponseCookieDataCollection
    {
        return $this->cookies;
    }

    /**
     * Get (or set) the HTTP response code
     *
     * Simply calling this method without any arguments returns the current response code.
     * Calling with an integer argument, however, attempts to set the response code to what
     * was provided by the argument.
     *
     * @param int|null $code The HTTP status code to send
     *
     * @return int|AbstractResponse
     */
    public function code(int $code = null): AbstractResponse|int|static
    {
        if (null !== $code) {
            // Require that the response be unlocked before changing it
            $this->requireUnlocked();

            $this->status = new HttpStatus($code);

            return $this;
        }

        return $this->status->getCode();
    }

    /**
     * Prepend a string to the response's content body
     *
     * @param string $content The string to prepend
     *
     * @return static
     */
    public function prepend(string $content): static
    {
        // Require that the response be unlocked before changing it
        $this->requireUnlocked();

        $this->body = $content . $this->body;

        return $this;
    }

    /**
     * Append a string to the response's content body
     *
     * @param string $content The string to append
     *
     * @return static
     */
    public function append(string $content): static
    {
        // Require that the response be unlocked before changing it
        $this->requireUnlocked();

        $this->body .= $content;

        return $this;
    }

    /**
     * Check if the response is locked
     *
     * @return boolean
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Require that the response is unlocked
     *
     * Throws an exception if the response is locked,
     * preventing any methods from mutating the response
     * when it's locked
     *
     * @return static
     * @throws LockedResponseException  If the response is locked
     */
    public function requireUnlocked(): static
    {
        if ($this->isLocked()) {
            throw new LockedResponseException('Response is locked');
        }

        return $this;
    }

    /**
     * Lock the response from further modification
     *
     * @return static
     */
    public function lock(): static
    {
        $this->locked = true;

        return $this;
    }

    /**
     * Unlock the response from further modification
     *
     * @return static
     */
    public function unlock(): static
    {
        $this->locked = false;

        return $this;
    }

    /**
     * Generates an HTTP compatible status header line string
     *
     * Creates the string based off of the response's properties
     *
     * @return string
     */
    protected function httpStatusLine(): string
    {
        return sprintf('HTTP/%s %s', $this->protocol_version, $this->status);
    }

    /**
     * Send our HTTP headers
     *
     * @param boolean $cookies_also Whether or not to also send the cookies after sending the normal headers
     * @param boolean $override Whether or not to override the check if headers have already been sent
     *
     * @return static
     */
    public function sendHeaders(bool $cookies_also = true, bool $override = false): static
    {
        if (headers_sent() && !$override) {
            return $this;
        }

        // Send our HTTP status line
        header($this->httpStatusLine());

        // Iterate through our Headers data collection and send each header
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value, false);
        }

        if ($cookies_also) {
            $this->sendCookies($override);
        }

        return $this;
    }

    /**
     * Send our HTTP response cookies
     *
     * @param boolean $override Whether to override the check if headers have already been sent
     *
     * @return static
     */
    public function sendCookies(bool $override = false): static
    {
        if (headers_sent() && !$override) {
            return $this;
        }

        // Iterate through our Cookies data collection and set each cookie natively
        /* @var $cookie ResponseCookie */
        foreach ($this->cookies as $cookie) {
            // Use the built-in PHP "setcookie" function
            setcookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpire() ?? 0,
                $cookie->getPath() ?? '',
                $cookie->getDomain() ?? '',
                $cookie->getSecure(),
                $cookie->getHttpOnly()
            );
        }

        return $this;
    }

    /**
     * Send our body's contents
     *
     * @return static
     */
    public function sendBody(): static
    {
        echo $this->body;

        return $this;
    }

    /**
     * Send the response and lock it
     *
     * @param boolean $override Whether or not to override the check if the response has already been sent
     *
     * @return static
     * @throws ResponseAlreadySentException If the response has already been sent
     */
    public function send(bool $override = false): static
    {
        if ($this->sent && !$override) {
            throw new ResponseAlreadySentException('Response has already been sent');
        }

        // Send our response data
        $this->sendHeaders();
        $this->sendBody();

        // Lock the response from further modification
        $this->lock();

        // Mark as sent
        $this->sent = true;

        // If there running FPM, tell the process manager to finish the server request/response handling
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        return $this;
    }

    /**
     * Check if the response has been sent
     *
     * @return boolean
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Enable response chunking
     *
     * @link https://github.com/klein/klein.php/wiki/Response-Chunking
     * @link http://bit.ly/hg3gHb
     * @return static
     */
    public function chunk(): static
    {
        if (false === $this->chunked) {
            $this->chunked = true;
            $this->header('Transfer-encoding', 'chunked');
            flush();
        }

        if (($body_length = strlen($this->body)) > 0) {
            printf("%x\r\n", $body_length);
            $this->sendBody();
            $this->body('');
            echo "\r\n";
            flush();
        }

        return $this;
    }

    /**
     * Sets a response header
     *
     * @param string $key The name of the HTTP response header
     * @param mixed $value The value to set the header with
     *
     * @return static
     */
    public function header(string $key, mixed $value): static
    {
        $this->headers->set($key, $value);

        return $this;
    }

    /**
     * Sets a response cookie
     *
     * @param string $key The name of the cookie
     * @param string|null $value The value to set the cookie with
     * @param int|null $expiry The time that the cookie should expire
     * @param string|null $path The path of which to restrict the cookie
     * @param string|null $domain The domain of which to restrict the cookie
     * @param boolean $secure Flag of whether the cookie should only be sent over a HTTPS connection
     * @param boolean $httponly Flag of whether the cookie should only be accessible over the HTTP protocol
     *
     * @return static
     */
    public function cookie(
        string $key,
        ?string $value = null,
        ?int $expiry = 0,
        ?string $path = '/',
        ?string $domain = null,
        ?bool $secure = false,
        bool $httponly = false
    ): static {
        if (null === $expiry) {
            $expiry = time() + (3600 * 24 * 30);
        }

        $this->cookies->set(
            $key,
            new ResponseCookie($key, $value ?? '', $expiry, $path, $domain, $secure, $httponly)
        );

        return $this;
    }

    /**
     * Tell the browser not to cache the response
     *
     * @return static
     */
    public function noCache(): static
    {
        $this->header('Pragma', 'no-cache');
        $this->header('Cache-Control', 'no-store, no-cache');

        return $this;
    }

    /**
     * Redirects the request to another URL
     *
     * @param string $url The URL to redirect to
     * @param int $code The HTTP status code to use for redirection
     *
     * @return static
     */
    public function redirect(string $url, int $code = 302): static
    {
        $this->code($code);
        $this->header('Location', $url);
        $this->lock();

        return $this;
    }
}
