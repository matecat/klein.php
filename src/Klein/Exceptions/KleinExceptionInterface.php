<?php /** @noinspection PhpMissingReturnTypeInspection */

/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/klein/klein.php
 * @license     MIT
 */

namespace Klein\Exceptions;

/**
 * KleinExceptionInterface
 *
 * Marker interface, implemented by all exceptions originating from Klein.
 *
 * Purpose:
 * - Provides a single type that can be used for catch blocks, type hints,
 *   and instanceof checks to uniformly handle framework-specific errors.
 * - Allows individual exception classes to extend appropriate SPL exception
 *   types (e.g., RuntimeException, InvalidArgumentException) while still
 *   being identifiable as Klein exceptions.
 *
 */
interface KleinExceptionInterface
{
    /**
     * Gets the message
     * @link https://php.net/manual/en/throwable.getmessage.php
     * @return string
     * @since 7.0
     */
    public function getMessage();

    /**
     * Gets the exception code
     * @link https://php.net/manual/en/throwable.getcode.php
     * @return int <p>
     * Returns the exception code as integer introduced in PHP 7.0.
     * </p>
     * @since 7.0
     */
    public function getCode();
}
