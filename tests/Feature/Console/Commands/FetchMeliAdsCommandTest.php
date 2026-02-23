<?php

declare(strict_types=1);

use App\Core\Application\UseCases\FetchSellerAdsUseCase;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns failure when seller id is empty and not in config', function () {
    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    config(['services.meli.seller_id' => null]);

    $this->artisan('meli:fetch-ads')
        ->assertExitCode(1)
        ->expectsOutputToContain('Seller ID is required');
});

it('returns success when seller id is provided via option', function () {
    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')
        ->with('12345', 30)
        ->once();

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    expect(Item::count())->toBe(0);

    $this->artisan('meli:fetch-ads', ['--seller-id' => '12345'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Successfully dispatched');

    expect(Item::count())->toBe(0); // Jobs ainda não foram processados
});

it('uses seller id from config when option not provided', function () {
    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')
        ->with('config-seller-id', 30)
        ->once();

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    config(['services.meli.seller_id' => 'config-seller-id']);

    expect(Item::count())->toBe(0);

    $this->artisan('meli:fetch-ads')
        ->assertExitCode(0)
        ->expectsOutputToContain('Successfully dispatched');
});

it('uses custom limit from option', function () {
    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')
        ->with('seller-id', 50)
        ->once();

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    $this->artisan('meli:fetch-ads', [
        '--seller-id' => 'seller-id',
        '--limit' => '50',
    ])
        ->assertExitCode(0);
});

it('skips execution when database has 30 or more ads without force flag', function () {
    Item::factory()->count(30)->create();

    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldNotReceive('execute');

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    expect(Item::count())->toBe(30);

    $this->artisan('meli:fetch-ads', ['--seller-id' => 'seller-id'])
        ->assertExitCode(0)
        ->expectsOutputToContain('already has 30 ads');
});

it('executes when database has 30+ ads with force flag', function () {
    Item::factory()->count(50)->create();

    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')
        ->with('seller-id', 30)
        ->once();

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    expect(Item::count())->toBe(50);

    $this->artisan('meli:fetch-ads', [
        '--seller-id' => 'seller-id',
        '--force' => true,
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('Force mode enabled');
});

it('displays current ad count', function () {
    Item::factory()->count(15)->create();

    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')->once();

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    expect(Item::count())->toBe(15);

    $this->artisan('meli:fetch-ads', ['--seller-id' => 'seller-id'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Current ads in database: 15');
});

it('catches and handles exceptions from use case', function () {
    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')
        ->andThrow(new Exception('API connection failed'));

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    expect(Item::count())->toBe(0);

    $this->artisan('meli:fetch-ads', ['--seller-id' => 'seller-id'])
        ->assertExitCode(1)
        ->expectsOutputToContain('Failed to fetch ads');
});

it('shows message about jobs processing when successful', function () {
    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')->once();

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    expect(Item::count())->toBe(0);

    $this->artisan('meli:fetch-ads', ['--seller-id' => 'seller-id'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Jobs will be processed by queue workers');
});

it('displays seller id being fetched', function () {
    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')
        ->with('specific-seller-999', 30)
        ->once();

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    expect(Item::count())->toBe(0);

    $this->artisan('meli:fetch-ads', ['--seller-id' => 'specific-seller-999'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Fetching up to 30 ads from seller: specific-seller-999');
});

it('converts limit option to integer', function () {
    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')
        ->with('seller-id', 75)
        ->once();

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    expect(Item::count())->toBe(0);

    $this->artisan('meli:fetch-ads', [
        '--seller-id' => 'seller-id',
        '--limit' => '75',
    ])
        ->assertExitCode(0);
});

it('returns success when ads count is less than 30', function () {
    // Criar 29 itens no banco
    Item::factory()->count(29)->create();

    $mockUseCase = Mockery::mock(FetchSellerAdsUseCase::class);
    $mockUseCase->shouldReceive('execute')
        ->with('seller-id', 30)
        ->once();

    $this->app->instance(FetchSellerAdsUseCase::class, $mockUseCase);

    expect(Item::count())->toBe(29);

    $this->artisan('meli:fetch-ads', ['--seller-id' => 'seller-id'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Successfully dispatched');
});
