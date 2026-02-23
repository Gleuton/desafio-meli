<?php

namespace App\Core\Infrastructure\Http\Clients;

use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class MeliSearchClient
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

        Log::info('[MeliSearchClient] Searching items by seller', [
            'url' => $url,
            'seller_id' => $sellerId,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $data = $this->httpClient->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Accept' => 'application/json',
            ],
        ]);

        Log::info('[MeliSearchClient] Search completed successfully', [
            'results_count' => count($data['results'] ?? []),
            'seller_id' => $sellerId,
        ]);

        return $data;
    }
}
