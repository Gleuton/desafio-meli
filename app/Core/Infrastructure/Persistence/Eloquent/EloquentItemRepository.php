<?php

namespace App\Core\Infrastructure\Persistence\Eloquent;

use App\Core\Application\DTOs\Input\ListItemsInputDTO;
use App\Core\Domain\Collections\ItemCollection;
use App\Core\Domain\Entities\Item as DomainItem;
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
            ['seller_id' => $sellerId]
        );
    }

    public function markAsProcessed(string $itemId): void
    {
        Item::where('meli_id', $itemId)->update([
            'processed_at' => Carbon::now(),
        ]);
    }

    public function markAsFailed(string $itemId, string $reason): void
    {
        Item::where('meli_id', $itemId)->update([
            'failed_reason' => $reason,
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
    ): ItemCollection {
        $query = Item::query();

        if ($inputDTO->sellerId !== null) {
            $query->where('seller_id', $inputDTO->sellerId);
        }

        $total = $query->count();

        $models = $query
            ->orderByDesc('created')
            ->paginate($inputDTO->perPage, ['*'], 'page', $inputDTO->page)
            ->getCollection();

        $items = $models->map(fn ($model) => ItemMapper::toDomain($model))->all();

        return new ItemCollection(
            items: $items,
            totalItems: $total,
            currentPage: $inputDTO->page,
            perPage: $inputDTO->perPage,
        );
    }
}
