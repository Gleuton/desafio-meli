<?php

namespace App\Core\Infrastructure\Http\Contracts;

interface HttpClientInterface
{
    /** @return array<string, mixed> */
    public function get(string $url, array $options): array;
}
