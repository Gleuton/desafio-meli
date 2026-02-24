<?php

namespace App\Jobs;

use App\Core\Application\Contracts\LoggerInterface;
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

            $repository->saveFromApi($itemData);
            $repository->markAsProcessed($this->itemId);

            $logger->info('Item successfully processed and saved', [
                'item_id' => $this->itemId,
                'title' => $itemData['title'] ?? 'N/A',
            ]);
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
            $repository->markAsFailed($this->itemId, $errorMessage);

            $logger->error('Failed to process item', [
                'item_id' => $this->itemId,
                'error' => $errorMessage,
                'exception' => get_class($e),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ]);

            throw $e;
        }
    }
}
