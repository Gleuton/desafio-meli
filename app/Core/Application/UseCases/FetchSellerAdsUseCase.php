<?php

namespace App\Core\Application\UseCases;

use App\Core\Application\Contracts\QueueDispatcherInterface;
use App\Core\Application\Messages\ProcessItemMessage;
use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use GuzzleHttp\Exception\GuzzleException;

class FetchSellerAdsUseCase
{
    private const PAGINATION_LIMIT = 5;

    public function __construct(
        private readonly MeliAuthClient $authClient,
        private readonly MeliSearchClient $searchClient,
        private readonly QueueDispatcherInterface $queueDispatcher,
        private readonly ItemRepositoryInterface $repository,
    ) {}

    public function execute(string $sellerId, int $maxAds = 30): void
    {
        $token = $this->retrieveActiveToken();

        if ($token === null) {
            return;
        }

        $this->fetchAndDispatchAds($sellerId, $token, $maxAds);
    }

    private function retrieveActiveToken(): ?string
    {
        $auth = $this->authClient->getToken();

        if (($auth['inactive_token'] ?? 1) !== 0) {
            return null;
        }

        return $auth['access_token'];
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
     * @return array<string, mixed>[]
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
     * @param  array<string, mixed>[]  $results
     */
    private function processResults(array $results, string $token, int $dispatchedCount, int $maxAds): int
    {
        foreach ($results as $item) {
            if (! $this->hasValidId($item)) {
                continue;
            }

            $this->repository->createPending($item['id']);

            $this->queueDispatcher->dispatch(
                new ProcessItemMessage($item['id'], $token)
            );

            $dispatchedCount++;

            if ($dispatchedCount >= $maxAds) {
                break;
            }
        }

        return $dispatchedCount;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function hasValidId(array $item): bool
    {
        return isset($item['id']);
    }
}
