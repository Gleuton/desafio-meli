<?php

namespace App\Jobs;

use App\Core\Application\Contracts\LoggerInterface;
use App\Core\Application\Exceptions\FailedToProcessItemException;
use App\Core\Infrastructure\Http\Clients\MeliItemsClient;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessItemJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 30;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly string $itemId,
        public readonly string $accessToken,
    ) {}

    /**
     * @throws FailedToProcessItemException
     */
    public function handle(
        MeliItemsClient $itemsClient,
        ItemRepositoryInterface $repository,
        LoggerInterface $logger
    ): void {
        try {
            $logger->info('Processing item job started', [
                'item_id' => $this->itemId,
                'attempt' => $this->attempts(),
            ]);

            $itemData = $itemsClient->getItem(
                itemId: $this->itemId,
                accessToken: $this->accessToken
            );

            $this->validateItemData($itemData);

            $repository->saveFromApi($itemData);
            $repository->markAsProcessed($this->itemId);

            $logger->info('Item successfully processed and saved', [
                'item_id' => $this->itemId,
                'title' => $itemData['title'] ?? 'N/A',
            ]);
        } catch (FailedToProcessItemException $e) {
            $repository->markAsFailed($this->itemId, $e->getMessage());
            $logger->error('Failed to process item', [
                'item_id' => $this->itemId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ]);
            throw $e;
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
            $exception = FailedToProcessItemException::fromApiError(
                $this->itemId,
                $errorMessage,
                $this->attempts(),
                $this->tries
            );

            $repository->markAsFailed($this->itemId, $errorMessage);

            $logger->error('Failed to process item', [
                'item_id' => $this->itemId,
                'error' => $errorMessage,
                'exception' => get_class($e),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $itemData
     *
     * @throws FailedToProcessItemException
     */
    private function validateItemData(array $itemData): void
    {
        if (empty($itemData['id'] ?? null)) {
            throw FailedToProcessItemException::invalidItemData(
                $this->itemId,
                'Missing required field: id'
            );
        }

        if (empty($itemData['title'] ?? null)) {
            throw FailedToProcessItemException::invalidItemData(
                $this->itemId,
                'Missing required field: title'
            );
        }
    }
}
