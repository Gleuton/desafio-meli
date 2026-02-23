<?php

namespace App\Core\Infrastructure\Http\Clients;

use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;

class MeliSearchClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
    ) {}

    public function searchBySeller(
        string $sellerId,
        string $accessToken,
        int $limit = 30,
        int $offset = 0
    ): array {
        $url = sprintf(
            '%s/mercadolibre/sites/MLB/search?seller_id=%s&offset=%d&limit=%d',
            rtrim($this->baseUrl, '/'),
            urlencode($sellerId),
            $offset,
            $limit
        );

        return $this->httpClient->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Accept' => 'application/json',
            ],
        ]);
    }
}
