<?php

namespace App\Core\Infrastructure\Persistence\Eloquent;

use App\Core\Application\DTOs\Input\ListItemsInputDTO;
use App\Core\Domain\Entities\Item as DomainItem;
use App\Core\Domain\Entities\PaginatedItem;
use App\Core\Domain\Enums\ProcessingStatus;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use App\Models\Item;
use Carbon\Carbon;

class EloquentItemRepository implements ItemRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $itemData
     */
    public function saveFromApi(array $itemData): void
    {
        Item::updateOrCreate(
            ['meli_id' => $itemData['id']],
            [
                'title' => $itemData['title'] ?? null,
                'status' => $itemData['status'] ?? null,
                'processing_status' => ProcessingStatus::PROCESSED,
                'processed_at' => Carbon::now(),
                'created' => isset($itemData['created']) ? Carbon::parse($itemData['created']) : null,
                'updated' => isset($itemData['updated']) ? Carbon::parse($itemData['updated']) : null,
            ]
        );
    }

    public function exists(string $itemId): bool
    {
        return Item::where('meli_id', $itemId)->exists();
    }

    public function count(): int
    {
        return Item::count();
    }

    public function createPending(string $itemId, ?string $sellerId = null): void
    {
        Item::firstOrCreate(
            ['meli_id' => $itemId],
            [
                'seller_id' => $sellerId,
                'processing_status' => ProcessingStatus::PENDING,
            ]
        );
    }

    public function markAsProcessing(string $itemId): void
    {
        Item::where('meli_id', $itemId)->update([
            'processed_at' => Carbon::now(),
            'processing_status' => ProcessingStatus::PROCESSING,
        ]);
    }

    public function markAsFailed(string $itemId, string $reason): void
    {
        Item::where('meli_id', $itemId)->update([
            'failed_reason' => $reason,
            'processing_status' => ProcessingStatus::FAILED,
        ]);
    }

    public function findById(string $itemId): ?DomainItem
    {
        $model = Item::where('meli_id', $itemId)
            ->first();

        return $model ? ItemMapper::toDomain($model) : null;
    }

    public function findPaginatedBySeller(
        ListItemsInputDTO $inputDTO
    ): PaginatedItem {
        $query = Item::query();

        if ($inputDTO->sellerId !== null) {
            $query->where('seller_id', $inputDTO->sellerId);
        }

        $total = $query->count();

        $models = $query
            ->orderByDesc('created')
            ->paginate($inputDTO->perPage, ['*'], 'page', $inputDTO->page)
            ->getCollection();

        $items = $models->map(
            fn ($model) => ItemMapper::toDomain($model)
        )->all();

        return new PaginatedItem(
            items: $items,
            totalItems: $total,
            currentPage: $inputDTO->page,
            perPage: $inputDTO->perPage,
        );
    }

    public function markAsProcessed(string $itemId): void {}
}
