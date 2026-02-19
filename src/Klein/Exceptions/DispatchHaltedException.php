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

namespace Klein\Exceptions;

use RuntimeException;

/**
 * DispatchHaltedException
 *
 * Exception used to halt a route callback from executing in a dispatch loop
 */
class DispatchHaltedException extends RuntimeException implements KleinExceptionInterface
{

    /**
     * Constants
     */

    /**
     * Skip this current match/callback
     *
     * @type int
     */
    public const int SKIP_THIS = 1;

    /**
     * Skip the next match/callback
     *
     * @type int
     */
    public const int SKIP_NEXT = 2;

    /**
     * Skip the rest of the matches
     *
     * @type int
     */
    public const int SKIP_REMAINING = 0;


    /**
     * Properties
     */

    /**
     * The number of next matches to skip on a "next" skip
     *
     * @type int
     */
    protected int $numberOfSkips = 1;


    /**
     * Methods
     */

    /**
     * Gets the number of matches to skip on a "next" skip
     *
     * @return int
     */
    public function getNumberOfSkips(): int
    {
        return $this->numberOfSkips;
    }

    /**
     * Sets the number of matches to skip on a "next" skip
     *
     * @param int $numberOfSkips
     *
     * @return DispatchHaltedException
     */
    public function setNumberOfSkips(int $numberOfSkips): DispatchHaltedException
    {
        $this->numberOfSkips = $numberOfSkips;

        return $this;
    }
}
