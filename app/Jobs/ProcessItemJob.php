<?php

namespace App\Jobs;

use App\Core\Application\Messages\ProcessItemMessage;
use App\Core\Infrastructure\Http\Clients\MeliItemsClient;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessItemJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProcessItemMessage $message
    ) {}

    public function handle(
        MeliItemsClient $itemsClient,
        ItemRepositoryInterface $repository
    ): void {
        try {
            $itemData = $itemsClient->getItem(
                itemId: $this->message->itemId,
                accessToken: $this->message->accessToken
            );

            $repository->saveFromApi($itemData);
            $repository->markAsProcessed($this->message->itemId);
        } catch (Throwable $e) {
            $repository->markAsFailed(
                $this->message->itemId,
                $e->getMessage()
            );

            throw $e;
        }
    }
}
