<?php
/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 19/02/26
 * Time: 13:53
 *
 */

declare(strict_types=1);

namespace Klein\Exceptions;

use InvalidArgumentException;
use Throwable;

class InvalidCallableException extends InvalidArgumentException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Expected a callable. Got an uncallable ' . $message, $code, $previous);
    }
}
