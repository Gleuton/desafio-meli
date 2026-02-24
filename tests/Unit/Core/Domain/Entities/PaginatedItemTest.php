<?php

declare(strict_types=1);

use App\Core\Domain\Entities\Item;
use App\Core\Domain\Entities\PaginatedItem;

function createTestItem(int $id): Item
{
    return new Item(
        id: $id,
        meliId: "MLB{$id}",
        sellerId: '252254392',
        title: "Sample Product {$id}",
        status: 'active',
        created: new DateTimeImmutable('2026-02-01 10:00:00'),
        updated: new DateTimeImmutable('2026-02-15 15:30:00'),
        processedAt: new DateTimeImmutable('2026-02-20 12:00:00'),
    );
}

it('creates a PaginatedItem with valid items', function () {
    $items = [
        createTestItem(1),
        createTestItem(2),
        createTestItem(3),
    ];

    $paginated = new PaginatedItem(
        items: $items,
        totalItems: 50,
        currentPage: 2,
        perPage: 3
    );

    expect($paginated->items)->toHaveCount(3)
        ->and($paginated->totalItems)->toBe(50)
        ->and($paginated->currentPage)->toBe(2)
        ->and($paginated->perPage)->toBe(3)
        ->and($paginated->totalPages)->toBe(17);
});

it('calculates total pages correctly when totalItems divides evenly by perPage', function () {
    $items = [
        createTestItem(1),
        createTestItem(2),
    ];

    $paginated = new PaginatedItem(
        items: $items,
        totalItems: 20,
        currentPage: 1,
        perPage: 10
    );

    expect($paginated->totalPages)->toBe(2);
});

it('calculates total pages correctly when totalItems does not divide evenly', function () {
    $items = [createTestItem(1)];

    $paginated = new PaginatedItem(
        items: $items,
        totalItems: 25,
        currentPage: 3,
        perPage: 10
    );

    expect($paginated->totalPages)->toBe(3);
});

it('handles single page correctly', function () {
    $items = [
        createTestItem(1),
        createTestItem(2),
    ];

    $paginated = new PaginatedItem(
        items: $items,
        totalItems: 2,
        currentPage: 1,
        perPage: 10
    );

    expect($paginated->totalPages)->toBe(1);
});

it('handles empty items array', function () {
    $paginated = new PaginatedItem(
        items: [],
        totalItems: 0,
        currentPage: 1,
        perPage: 15
    );

    expect($paginated->items)->toBeEmpty()
        ->and($paginated->totalItems)->toBe(0)
        ->and($paginated->totalPages)->toBe(0);
});

it('throws InvalidArgumentException when items array contains non-Item objects', function () {
    expect(fn () => new PaginatedItem(
        items: ['not an item', 'another non-item'],
        totalItems: 2,
        currentPage: 1,
        perPage: 15
    ))->toThrow(InvalidArgumentException::class, 'Invalid item type');
});

it('throws InvalidArgumentException when items array contains mixed types', function () {
    $items = [
        createTestItem(1),
        'not an item',
        createTestItem(2),
    ];

    expect(fn () => new PaginatedItem(
        items: $items,
        totalItems: 3,
        currentPage: 1,
        perPage: 15
    ))->toThrow(InvalidArgumentException::class, 'Invalid item type');
});

it('validates all items in the array', function () {
    $items = [
        createTestItem(1),
        createTestItem(2),
        createTestItem(3),
        createTestItem(4),
        createTestItem(5),
    ];

    $paginated = new PaginatedItem(
        items: $items,
        totalItems: 100,
        currentPage: 1,
        perPage: 5
    );

    expect($paginated->items)->toHaveCount(5);

    foreach ($paginated->items as $item) {
        expect($item)->toBeInstanceOf(Item::class);
    }
});

it('handles large per page values correctly', function () {
    $items = [createTestItem(1)];

    $paginated = new PaginatedItem(
        items: $items,
        totalItems: 1,
        currentPage: 1,
        perPage: 100
    );

    expect($paginated->totalPages)->toBe(1);
});

it('handles edge case with perPage equals 1', function () {
    $items = [createTestItem(1)];

    $paginated = new PaginatedItem(
        items: $items,
        totalItems: 50,
        currentPage: 25,
        perPage: 1
    );

    expect($paginated->totalPages)->toBe(50);
});

it('stores items correctly and maintains their order', function () {
    $items = [
        createTestItem(5),
        createTestItem(3),
        createTestItem(7),
    ];

    $paginated = new PaginatedItem(
        items: $items,
        totalItems: 10,
        currentPage: 1,
        perPage: 3
    );

    expect($paginated->items[0]->id)->toBe(5)
        ->and($paginated->items[1]->id)->toBe(3)
        ->and($paginated->items[2]->id)->toBe(7);
});

it('creates PaginatedItem with all item properties intact', function () {
    $item = new Item(
        id: 999,
        meliId: 'MLB999',
        sellerId: '123456789',
        title: 'Detailed Product Title',
        status: 'paused',
        created: new DateTimeImmutable('2026-01-15 08:00:00'),
        updated: new DateTimeImmutable('2026-02-10 14:30:00'),
        processedAt: new DateTimeImmutable('2026-02-22 09:45:00'),
    );

    $paginated = new PaginatedItem(
        items: [$item],
        totalItems: 1,
        currentPage: 1,
        perPage: 15
    );

    $storedItem = $paginated->items[0];

    expect($storedItem->id)->toBe(999)
        ->and($storedItem->meliId)->toBe('MLB999')
        ->and($storedItem->sellerId)->toBe('123456789')
        ->and($storedItem->title)->toBe('Detailed Product Title')
        ->and($storedItem->status)->toBe('paused')
        ->and($storedItem->created->format('Y-m-d H:i:s'))->toBe('2026-01-15 08:00:00')
        ->and($storedItem->updated->format('Y-m-d H:i:s'))->toBe('2026-02-10 14:30:00')
        ->and($storedItem->processedAt->format('Y-m-d H:i:s'))->toBe('2026-02-22 09:45:00');
});

it('handles null processedAt in items', function () {
    $item = new Item(
        id: 100,
        meliId: 'MLB100',
        sellerId: '111111111',
        title: 'Unprocessed Item',
        status: 'pending',
        created: new DateTimeImmutable('2026-02-20 10:00:00'),
        updated: new DateTimeImmutable('2026-02-20 10:00:00'),
        processedAt: null,
    );

    $paginated = new PaginatedItem(
        items: [$item],
        totalItems: 1,
        currentPage: 1,
        perPage: 15
    );

    expect($paginated->items[0]->processedAt)->toBeNull();
});
