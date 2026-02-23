<?php

namespace App\Core\Application\UseCases;

use App\Core\Application\Contracts\QueueDispatcherInterface;
use App\Core\Application\Messages\ProcessItemMessage;
use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;

class FetchSellerAdsUseCase
{
    public function __construct(
        private readonly MeliAuthClient $authClient,
        private readonly MeliSearchClient $searchClient,
        private readonly QueueDispatcherInterface $queueDispatcher,
    ) {}

    public function execute(string $sellerId, int $maxAds = 30): void
    {
        $auth = $this->authClient->getToken();

        if (($auth['inactive_token'] ?? 1) !== 0) {
            return;
        }

        $token = $auth['access_token'];

        $offset = 0;
        $limit = 5;
        $collected = 0;

        while ($collected < $maxAds) {
            $response = $this->searchClient->searchBySeller(
                sellerId: $sellerId,
                accessToken: $token,
                limit: $limit,
                offset: $offset
            );

            $results = $response['results'] ?? [];

            if (empty($results)) {
                break;
            }

            foreach ($results as $item) {
                if (! isset($item['id'])) {
                    continue;
                }

                $this->queueDispatcher->dispatch(
                    new ProcessItemMessage($item['id'], $token)
                );

                $collected++;

                if ($collected >= $maxAds) {
                    break 2;
                }
            }

            $offset += $limit;
        }
    }
}
