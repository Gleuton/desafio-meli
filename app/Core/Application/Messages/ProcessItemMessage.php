<?php

namespace App\Core\Application\Messages;

class ProcessItemMessage
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $accessToken,
    ) {}
}
