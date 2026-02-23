<?php

namespace App\Core\Infrastructure\Persistence;

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
                'category_id' => $itemData['category_id'] ?? null,
                'price' => $itemData['price'] ?? null,
                'currency_id' => $itemData['currency_id'] ?? null,
                'condition' => $itemData['condition'] ?? null,
                'listing_type_id' => $itemData['listing_type_id'] ?? null,
                'permalink' => $itemData['permalink'] ?? null,
                'thumbnail' => $itemData['thumbnail'] ?? null,
                'seller_id' => $itemData['seller_id'] ?? null,
                'status' => 'processed',
                'raw_payload' => $itemData,
                'processed_at' => Carbon::now(),
                'failed_reason' => null,
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

    public function createPending(string $itemId): void
    {
        Item::firstOrCreate(
            ['meli_id' => $itemId],
            ['status' => 'pending']
        );
    }

    public function markAsProcessed(string $itemId): void
    {
        Item::where('meli_id', $itemId)->update([
            'status' => 'processed',
            'processed_at' => Carbon::now(),
            'failed_reason' => null,
        ]);
    }

    public function markAsFailed(string $itemId, string $reason): void
    {
        Item::where('meli_id', $itemId)->update([
            'status' => 'failed',
            'failed_reason' => $reason,
        ]);
    }
}
