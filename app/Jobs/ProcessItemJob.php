<?php

namespace App\Jobs;

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
        ItemRepositoryInterface $repository
    ): void {
        try {
            $itemData = $itemsClient->getItem(
                itemId: $this->itemId,
                accessToken: $this->accessToken
            );

            $repository->saveFromApi($itemData);
            $repository->markAsProcessed($this->itemId);
        } catch (Throwable $e) {
            $repository->markAsFailed(
                $this->itemId,
                $e->getMessage()
            );

            throw $e;
        }
    }
}
