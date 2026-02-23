<?php

namespace App\Core\Infrastructure\Http\Clients;

use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class MeliAuthClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $sellerId,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     */
    public function getToken(): array
    {
        $url = sprintf(
            '%s/traymeli/sellers/%s',
            rtrim($this->baseUrl, '/'),
            urlencode($this->sellerId)
        );

        Log::info('[MeliAuthClient] Requesting token', ['url' => $url]);

        try {
            $data = $this->httpClient->get($url, []);

            Log::info('[MeliAuthClient] Token received successfully', [
                'has_access_token' => isset($data['access_token']),
                'inactive_token' => $data['inactive_token'] ?? null,
            ]);

            return $data;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 429) {
                Log::warning('[MeliAuthClient] Rate limit detected (429)', [
                    'url' => $url,
                    'seller_id' => $this->sellerId,
                    'response_body' => (string) $e->getResponse()->getBody(),
                ]);
            }

            throw $e;
        }
    }
}
