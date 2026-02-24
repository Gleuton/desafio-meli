<?php

declare(strict_types=1);

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 200 with items list', function () {
    Item::factory()->create();

    $response = $this->get('/api/v1/items');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'meli_id',
                    'seller_id',
                    'title',
                    'status',
                    'created_at',
                    'updated_at',
                    'processed_at',
                ],
            ],
            'pagination' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
        ]);

    expect($response->json('status'))->toBe('success')
        ->and($response->json('pagination.total'))->toBe(1);
});

it('returns empty list when no items exist', function () {
    $response = $this->get('/api/v1/items');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => 15,
                'total' => 0,
                'last_page' => 0,
            ],
        ]);
});

it('filters items by seller_id', function () {
    Item::factory()->create(['seller_id' => '252254392']);

    Item::factory()->create(['seller_id' => '999999999']);

    $response = $this->get('/api/v1/items?seller_id=252254392');

    $response->assertStatus(200);
    expect($response->json('pagination.total'))->toBe(1)
        ->and($response->json('data.0.seller_id'))->toBe('252254392');
});

it('respects per_page parameter', function () {
    Item::factory(20)->create(['seller_id' => '252254392']);

    $response = $this->get('/api/v1/items?per_page=10');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(10)
        ->and($response->json('pagination.per_page'))->toBe(10)
        ->and($response->json('pagination.total'))->toBe(20);
});

it('respects page parameter', function () {
    Item::factory(30)->create(['seller_id' => '252254392']);

    $response = $this->get('/api/v1/items?page=2&per_page=15');

    $response->assertStatus(200);
    expect($response->json('pagination.current_page'))->toBe(2)
        ->and($response->json('pagination.per_page'))->toBe(15)
        ->and($response->json('pagination.total'))->toBe(30)
        ->and(count($response->json('data')))->toBe(15);
});

it('returns 422 when page is less than 1', function () {
    $response = $this->get('/api/v1/items?page=0');

    $response->assertInvalid('page');
});

it('returns 422 when per_page exceeds 100', function () {
    $response = $this->get('/api/v1/items?per_page=101');

    $response->assertInvalid('per_page');
});

it('returns 422 when per_page is 0', function () {
    $response = $this->get('/api/v1/items?per_page=0');

    $response->assertInvalid('per_page');
});

it('returns items ordered by created DESC', function () {
    $now = now();

    Item::factory()->create([
        'meli_id' => 'MLB0000000001',
        'seller_id' => '252254392',
        'created' => $now->subHour(),
        'updated' => $now->subHour(),
    ]);

    Item::factory()->create([
        'meli_id' => 'MLB0000000002',
        'seller_id' => '252254392',
        'created' => now(),
        'updated' => now(),
    ]);

    $response = $this->get('/api/v1/items');

    $response->assertStatus(200);

    expect($response->json('data.0.meli_id'))->toBe('MLB0000000002')
        ->and($response->json('data.1.meli_id'))->toBe('MLB0000000001');
});

it('includes processed_at in response when item is processed', function () {
    Item::factory()->create([
        'seller_id' => '252254392',
        'processed_at' => now(),
    ]);

    $response = $this->get('/api/v1/items');

    $response->assertStatus(200);
    expect($response->json('data.0.processed_at'))->not->toBeNull();
});

it('includes null for processed_at when item is not processed', function () {
    Item::factory()->pending()->create([
        'seller_id' => '252254392',
    ]);

    $response = $this->get('/api/v1/items');

    $response->assertStatus(200);
    expect($response->json('data.0.processed_at'))->toBeNull();
});
