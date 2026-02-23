<?php

namespace App\Core\Application\Commands;

/**
 * @codeCoverageIgnore
 */
class ProcessItemCommand
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $accessToken,
    ) {}
}
