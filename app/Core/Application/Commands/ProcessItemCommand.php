<?php

namespace App\Core\Application\Commands;

class ProcessItemCommand
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $accessToken,
    ) {}
}
