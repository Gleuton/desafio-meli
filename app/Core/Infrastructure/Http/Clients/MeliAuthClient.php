<?php

namespace App\Core\Infrastructure\Http\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MeliAuthClient
{
    private string $baseUrl;

    private string $sellerId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.meli.base_url'), '/');
        $this->sellerId = config('services.meli.seller_id');
    }

    /**
     * @return array{
     *   store_id?: string,
     *   user_id?: int,
     *   access_token?: string,
     *   inactive_token?: int
     * }|null
     */
    public function getToken(): ?array
    {
        $url = "{$this->baseUrl}/traymeli/sellers/{$this->sellerId}";

        try {
            Log::info("[MeliAuthClient] Requesting token: {$url}");

            $response = Http::timeout(5)->get($url);

            if ($response->status() === 429) {
                Log::warning('[MeliAuthClient] Rate limit (429)');

                return null;
            }

            if (! $response->successful()) {
                Log::error('[MeliAuthClient] Request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            Log::info('[MeliAuthClient] Response received', $data);

            return $data;

        } catch (Throwable $e) {
            Log::error('[MeliAuthClient] Exception while requesting token', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
