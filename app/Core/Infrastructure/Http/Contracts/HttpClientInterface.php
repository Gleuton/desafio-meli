<?php

namespace App\Core\Infrastructure\Http\Contracts;

use GuzzleHttp\Exception\GuzzleException;

interface HttpClientInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     */
    public function get(string $url, array $options): array;
}
