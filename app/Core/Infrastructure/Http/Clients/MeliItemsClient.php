<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Http\Clients;

use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class MeliItemsClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     */
    public function getItem(string $itemId, string $accessToken): array
    {
        $url = sprintf(
            '%s/mercadolibre/items/%s',
            rtrim($this->baseUrl, '/'),
            urlencode($itemId)
        );

        return $this->httpClient->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Accept' => 'application/json',
            ],
        ]);
    }
}
