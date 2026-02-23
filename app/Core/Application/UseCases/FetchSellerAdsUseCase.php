<?php

namespace App\Core\Application\UseCases;

use App\Core\Application\Contracts\QueueDispatcherInterface;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use App\Jobs\ProcessItemJob;
use GuzzleHttp\Exception\GuzzleException;

class FetchSellerAdsUseCase
{
    private const PAGINATION_LIMIT = 5;

    public function __construct(
        private readonly MeliSearchClient $searchClient,
        private readonly QueueDispatcherInterface $queueDispatcher,
        private readonly ItemRepositoryInterface $repository,
    ) {}

    public function execute(string $sellerId, string $token, int $maxAds = 30): void
    {
        $this->fetchAndDispatchAds($sellerId, $token, $maxAds);
    }

    private function fetchAndDispatchAds(string $sellerId, string $token, int $maxAds): void
    {
        $offset = 0;
        $dispatchedCount = 0;

        while ($dispatchedCount < $maxAds) {
            $results = $this->searchSeller($sellerId, $token, $offset);

            if (empty($results)) {
                break;
            }

            $dispatchedCount = $this->processResults($results, $token, $dispatchedCount, $maxAds);

            if ($dispatchedCount >= $maxAds) {
                break;
            }

            $offset += self::PAGINATION_LIMIT;
        }
    }

    /**
     * @return array<int, string> Array of item IDs
     *
     * @throws GuzzleException
     */
    private function searchSeller(string $sellerId, string $token, int $offset): array
    {
        $response = $this->searchClient->searchBySeller(
            sellerId: $sellerId,
            accessToken: $token,
            limit: self::PAGINATION_LIMIT,
            offset: $offset
        );

        return $response['results'] ?? [];
    }

    /**
     * @param  array<int, string>  $results  Array of item IDs
     */
    private function processResults(array $results, string $token, int $dispatchedCount, int $maxAds): int
    {
        foreach ($results as $itemId) {
            if (! is_string($itemId) || $itemId === '') {
                continue;
            }

            $this->repository->createPending($itemId);

            $this->queueDispatcher->dispatch(
                new ProcessItemJob($itemId, $token),
            );

            $dispatchedCount++;

            if ($dispatchedCount >= $maxAds) {
                break;
            }
        }

        return $dispatchedCount;
    }
}
