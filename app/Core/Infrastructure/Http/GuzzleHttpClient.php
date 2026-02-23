<?php

namespace App\Core\Infrastructure\Http;

use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

class GuzzleHttpClient implements HttpClientInterface
{
    public function __construct(private readonly Client $client) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function get(string $url, array $options): array
    {
        $response = $this->client->get($url, $options);

        return json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
