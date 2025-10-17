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

use BadMethodCallException;
use Klein\Exceptions\ValidationException;

/**
 * Validator
 */
class Validator
{

    /**
     * Class properties
     */

    /**
     * The available validator methods
     *
     * @var array<string, callable>
     */
    public static array $methods = [];

    /**
     * The string to validate
     *
     * @type ?string
     */
    protected ?string $str;

    /**
     * The custom exception message to throw on validation failure
     *
     * @type string|null
     */
    protected ?string $err;

    /**
     * Flag for whether the default validation methods have been added or not
     *
     * @type boolean
     */
    protected static bool $default_added = false;


    /**
     * Methods
     */

    /**
     * Sets up the validator chain with the string and optional error message
     *
     * @param string|null $str The string to validate
     * @param string|null $err The optional custom exception message to throw on validation failure
     */
    public function __construct(?string $str = null, ?string $err = null)
    {
        $this->str = $str;
        $this->err = $err;

        if (!static::$default_added) {
            static::addDefault();
        }
    }

    /**
     * Adds default validators on first use
     *
     * @return void
     */
    public static function addDefault(): void
    {
        static::$methods['null'] = function (?string $str) {
            return $str === null || $str === '';
        };
        static::$methods['len'] = function (?string $str, int $min, ?int $max = null) {
            $len = strlen($str);
            return null === $max ? $len === $min : $len >= $min && $len <= $max;
        };
        static::$methods['int'] = function (?string $str) {
            return (string)$str === ((string)(int)$str);
        };
        static::$methods['float'] = function (?string $str) {
            return (string)$str === ((string)(float)$str);
        };
        static::$methods['email'] = function (?string $str) {
            return filter_var($str, FILTER_VALIDATE_EMAIL) !== false;
        };
        static::$methods['url'] = function (?string $str) {
            return filter_var($str, FILTER_VALIDATE_URL) !== false;
        };
        static::$methods['ip'] = function (?string $str) {
            return filter_var($str, FILTER_VALIDATE_IP) !== false;
        };
        static::$methods['remoteip'] = function (?string $str) {
            return filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        };
        static::$methods['alnum'] = function (?string $str) {
            return ctype_alnum($str);
        };
        static::$methods['alpha'] = function (?string $str) {
            return ctype_alpha($str);
        };
        static::$methods['contains'] = function (?string $str, string $needle) {
            return str_contains($str, $needle);
        };
        static::$methods['regex'] = function (?string $str, string $pattern) {
            return preg_match($pattern, $str);
        };
        static::$methods['chars'] = function (?string $str, string $chars) {
            return preg_match("/^[$chars]++$/i", $str);
        };

        static::$default_added = true;
    }

    /**
     * Add a custom validator to our list of validation methods
     *
     * @param string $method The name of the validator method
     * @param callable $callback The callback to perform on validation
     *
     * @return void
     */
    public static function addValidator(string $method, callable $callback): void
    {
        static::$methods[strtolower($method)] = $callback;
    }

    /**
     * Magic "__call" method
     *
     * Allows the ability to arbitrarily call a validator with an optional prefix
     * of "is" or "not" by simply calling an instance property like a callback
     *
     * @param string $method The callable method to execute
     * @param array<mixed> $args The argument array to pass to our callback
     *
     * @return static
     * @throws ValidationException
     * @throws BadMethodCallException   If a non-registered method is attempted to be called
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function __call(string $method, array $args): static
    {
        $reverse = false;
        $validator = $method;
        $method_substr = substr($method, 0, 2);

        if ($method_substr === 'is') {       // is<$validator>()
            $validator = substr($method, 2);
        } elseif ($method_substr === 'no') { // not<$validator>()
            $validator = substr($method, 3);
            $reverse = true;
        }

        $validator = strtolower($validator);

        if (!$validator || !isset(static::$methods[$validator])) {
            throw new BadMethodCallException('Unknown method ' . $method . '()');
        }

        $validator = static::$methods[$validator];
        array_unshift($args, $this->str);
        $result = $validator(...$args);

        $result = (bool)($result ^ $reverse);

        if (!$result) {
            throw new ValidationException($this->err ?: 'Validation failed');
        }

        return $this;
    }
}
