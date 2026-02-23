<?php

namespace App\Core\Application\Messages;

class ProcessItemMessage
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $accessToken,
    ) {}

    public function __invoke(): array
    {
        return [
            'itemId' => $this->itemId,
            'accessToken' => $this->accessToken,
        ];
    }
}
