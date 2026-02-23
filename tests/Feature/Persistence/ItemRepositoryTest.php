<?php

use App\Core\Infrastructure\Persistence\EloquentItemRepository;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves item from api payload', function () {
    $repo = new EloquentItemRepository;

    $payload = [
        'id' => 'MLB123',
        'title' => 'Produto Teste',
        'price' => 199.90,
        'currency_id' => 'BRL',
        'seller_id' => '252254392',
    ];

    $repo->saveFromApi($payload);

    expect(Item::where('meli_id', 'MLB123')->exists())->toBeTrue();

    $this->assertDatabaseHas(
        'items',
        [
            'meli_id' => $payload['id'],
            'title' => $payload['title'],
            'price' => $payload['price'],
            'currency_id' => $payload['currency_id'],
            'seller_id' => $payload['seller_id'],
        ]
    );
});

it('updates existing item instead of duplicating', function () {
    $repo = new EloquentItemRepository;

    $payload1 = [
        'id' => 'MLB123',
        'title' => 'Produto A',
    ];

    $payload2 = [
        'id' => 'MLB123',
        'title' => 'Produto B',
    ];

    $repo->saveFromApi($payload1);
    $repo->saveFromApi($payload2);

    $item = Item::where('meli_id', 'MLB123')->first();

    expect($item->title)->toBe('Produto B');

    $this->assertDatabaseHas(
        'items',
        [
            'meli_id' => $payload2['id'],
            'title' => $payload2['title'],
        ]
    );
});

it('marks item as failed', function () {
    $repo = new EloquentItemRepository;

    $repo->saveFromApi(['id' => 'MLB999', 'title' => 'X']);

    $repo->markAsFailed('MLB999', 'API error');

    $item = Item::where('meli_id', 'MLB999')->first();

    expect($item->status)->toBe('failed')
        ->and($item->failed_reason)->toBe('API error');
    $this->assertDatabaseHas(
        'items',
        [
            'meli_id' => 'MLB999',
            'status' => 'failed',
            'failed_reason' => 'API error',
        ]
    );
});
