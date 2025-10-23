<?php

namespace Klein\Tests\Fixtures;

use Closure;

readonly class ClosureTestClass
{
    public function __construct(
        public string $path,
        public string $result,
        public string $registerPath,
        public Closure $closure,
    ) {
    }
}