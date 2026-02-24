<?php

namespace App\Core\Application\UseCases;

use App\Core\Application\Contracts\LoggerInterface;
use App\Core\Application\Contracts\QueueDispatcherInterface;
use App\Core\Application\Exceptions\FailedToFetchAdsException;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use App\Jobs\ProcessItemJob;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class FetchSellerAdsUseCase
{
    private const PAGINATION_LIMIT = 5;

    public function __construct(
        private readonly MeliSearchClient $searchClient,
        private readonly QueueDispatcherInterface $queueDispatcher,
        private readonly ItemRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @throws FailedToFetchAdsException When API communication fails
     */
    public function execute(string $sellerId, string $token, int $maxAds = 30): void
    {
        try {
            $this->logger->info('Starting to fetch ads from seller', [
                'seller_id' => $sellerId,
                'max_ads' => $maxAds,
            ]);

            $this->fetchAndDispatchAds($sellerId, $token, $maxAds);

            $this->logger->info('Successfully completed fetching ads', [
                'seller_id' => $sellerId,
            ]);
        } catch (FailedToFetchAdsException $e) {
            $this->logger->error('API error while fetching ads', [
                'seller_id' => $sellerId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } catch (ConnectException $e) {
            $exception = FailedToFetchAdsException::networkTimeout($sellerId);
            $this->logger->error('Network timeout while fetching ads', [
                'seller_id' => $sellerId,
                'error' => $e->getMessage(),
            ]);
            throw $exception;
        } catch (RequestException $e) {
            $exception = FailedToFetchAdsException::fromApiError($sellerId, $e->getMessage());
            $this->logger->error('Request error while fetching ads', [
                'seller_id' => $sellerId,
                'error' => $e->getMessage(),
                'http_code' => $e->getCode(),
            ]);
            throw $exception;
        } catch (\Throwable $e) {
            $exception = FailedToFetchAdsException::fromApiError($sellerId, $e->getMessage());
            $this->logger->error('Unexpected error while fetching ads', [
                'seller_id' => $sellerId,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $exception;
        }
    }

    /**
     * @throws FailedToFetchAdsException
     */
    private function fetchAndDispatchAds(string $sellerId, string $token, int $maxAds): void
    {
        $offset = 0;
        $dispatchedCount = 0;
        $pageNumber = 0;

        while ($dispatchedCount < $maxAds) {
            $pageNumber++;
            $this->logger->debug('Fetching ads page', [
                'seller_id' => $sellerId,
                'page' => $pageNumber,
                'offset' => $offset,
                'dispatched_count' => $dispatchedCount,
            ]);

            $results = $this->searchSeller($sellerId, $token, $offset);

            if (empty($results)) {
                $this->logger->debug('No more results from API, pagination ended', [
                    'seller_id' => $sellerId,
                    'page' => $pageNumber,
                ]);
                break;
            }

            $this->logger->debug('Received results from API', [
                'seller_id' => $sellerId,
                'page' => $pageNumber,
                'result_count' => count($results),
            ]);

            $dispatchedCount = $this->processResults($results, $sellerId, $token, $dispatchedCount, $maxAds);

            if ($dispatchedCount >= $maxAds) {
                break;
            }

            $offset += self::PAGINATION_LIMIT;
        }

        $this->logger->info('Finished fetching and dispatching ads', [
            'seller_id' => $sellerId,
            'total_dispatched' => $dispatchedCount,
            'pages_processed' => $pageNumber,
        ]);
    }

    /**
     * @return array<int, string>
     *
     * @throws FailedToFetchAdsException
     */
    private function searchSeller(string $sellerId, string $token, int $offset): array
    {
        try {
            $response = $this->searchClient->searchBySeller(
                sellerId: $sellerId,
                accessToken: $token,
                limit: self::PAGINATION_LIMIT,
                offset: $offset
            );

            return $response['results'] ?? [];
        } catch (ConnectException $e) {
            throw FailedToFetchAdsException::networkTimeout($sellerId);
        } catch (RequestException $e) {
            throw FailedToFetchAdsException::fromApiError($sellerId, $e->getMessage());
        } catch (\Throwable $e) {
            throw FailedToFetchAdsException::fromApiError($sellerId, $e->getMessage());
        }
    }

    /**
     * @param  array<int, string>  $results
     */
    private function processResults(array $results, string $sellerId, string $token, int $dispatchedCount, int $maxAds): int
    {
        $skippedCount = 0;

        foreach ($results as $itemId) {
            if (! is_string($itemId) || $itemId === '') {
                $skippedCount++;
                $this->logger->debug('Skipped invalid item', [
                    'seller_id' => $sellerId,
                    'reason' => 'Invalid item ID (empty or not string)',
                ]);

                continue;
            }

            $this->repository->createPending($itemId, $sellerId);

            $this->queueDispatcher->dispatch(
                new ProcessItemJob($itemId, $token),
            );

            $dispatchedCount++;

            $this->logger->debug('Dispatched item for processing', [
                'seller_id' => $sellerId,
                'item_id' => $itemId,
                'dispatched_count' => $dispatchedCount,
            ]);

            if ($dispatchedCount >= $maxAds) {
                break;
            }
        }

        if ($skippedCount > 0) {
            $this->logger->warning('Some items were skipped due to invalid data', [
                'seller_id' => $sellerId,
                'skipped_count' => $skippedCount,
            ]);
        }

        return $dispatchedCount;
    }
}
