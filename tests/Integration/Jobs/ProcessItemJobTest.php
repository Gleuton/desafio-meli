<?php

declare(strict_types=1);

use App\Core\Application\Messages\ProcessItemMessage;
use App\Core\Infrastructure\Http\Clients\MeliItemsClient;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use App\Jobs\ProcessItemJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('successfully handles item processing', function () {
    $itemData = [
        'id' => 'ITEM_123',
        'title' => 'Test Item',
        'price' => 99.99,
    ];

    $mockItemsClient = Mockery::mock(MeliItemsClient::class);
    $mockItemsClient->shouldReceive('getItem')
        ->with('ITEM_123', 'access-token-123')
        ->andReturn($itemData);

    $mockRepository = Mockery::mock(ItemRepositoryInterface::class);
    $mockRepository->shouldReceive('saveFromApi')
        ->with($itemData)
        ->once();
    $mockRepository->shouldReceive('markAsProcessed')
        ->with('ITEM_123')
        ->once();

    $message = new ProcessItemMessage('ITEM_123', 'access-token-123');
    $job = new ProcessItemJob($message);

    $job->handle($mockItemsClient, $mockRepository);
});

it('marks item as failed when client throws exception', function () {
    $exception = new Exception('API Error');

    $mockItemsClient = Mockery::mock(MeliItemsClient::class);
    $mockItemsClient->shouldReceive('getItem')
        ->andThrow($exception);

    $mockRepository = Mockery::mock(ItemRepositoryInterface::class);
    $mockRepository->shouldReceive('markAsFailed')
        ->with('ITEM_456', 'API Error')
        ->once();

    $message = new ProcessItemMessage('ITEM_456', 'access-token-456');
    $job = new ProcessItemJob($message);

    expect(fn () => $job->handle($mockItemsClient, $mockRepository))
        ->toThrow(Exception::class);
});

it('marks item as failed before re-throwing exception', function () {
    $exception = new RuntimeException('Connection failed');

    $mockItemsClient = Mockery::mock(MeliItemsClient::class);
    $mockItemsClient->shouldReceive('getItem')
        ->andThrow($exception);

    $mockRepository = Mockery::mock(ItemRepositoryInterface::class);
    $callOrder = [];
    $mockRepository->shouldReceive('markAsFailed')
        ->andReturnUsing(function () use (&$callOrder) {
            $callOrder[] = 'markAsFailed';
        })
        ->once();

    $message = new ProcessItemMessage('ITEM_789', 'token');
    $job = new ProcessItemJob($message);

    try {
        $job->handle($mockItemsClient, $mockRepository);
    } catch (RuntimeException) {
        // Expected
    }

    expect($callOrder)->toBe(['markAsFailed']);
});

it('passes correct item id and access token to client', function () {
    $itemId = 'ITEM_SPECIFIC_123';
    $accessToken = 'specific-token-456';

    $mockItemsClient = Mockery::mock(MeliItemsClient::class);
    $mockItemsClient->shouldReceive('getItem')
        ->with($itemId, $accessToken)
        ->andReturn(['id' => $itemId])
        ->once();

    $mockRepository = Mockery::mock(ItemRepositoryInterface::class);
    $mockRepository->shouldReceive('saveFromApi')->once();
    $mockRepository->shouldReceive('markAsProcessed')->once();

    $message = new ProcessItemMessage($itemId, $accessToken);
    $job = new ProcessItemJob($message);

    $job->handle($mockItemsClient, $mockRepository);
});

it('has correct job configuration', function () {
    $message = new ProcessItemMessage('ITEM_123', 'token');
    $job = new ProcessItemJob($message);

    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(60)
        ->and($job->backoff)->toBe(30)
        ->and($job->failOnTimeout)->toBeTrue();
});

it('stores message data in constructor', function () {
    $itemId = 'ITEM_CONSTRUCTOR';
    $accessToken = 'token-constructor';

    $message = new ProcessItemMessage($itemId, $accessToken);
    $job = new ProcessItemJob($message);

    expect($job->message->itemId)->toBe($itemId)
        ->and($job->message->accessToken)->toBe($accessToken);
});

it('does not call save when client fails', function () {
    $mockItemsClient = Mockery::mock(MeliItemsClient::class);
    $mockItemsClient->shouldReceive('getItem')
        ->andThrow(new Exception('Failed'));

    $mockRepository = Mockery::mock(ItemRepositoryInterface::class);
    $mockRepository->shouldNotReceive('saveFromApi');
    $mockRepository->shouldReceive('markAsFailed')->once();

    $message = new ProcessItemMessage('ITEM_123', 'token');
    $job = new ProcessItemJob($message);

    try {
        $job->handle($mockItemsClient, $mockRepository);
    } catch (Exception) {

    }
});

it('handles client returning complex item data', function () {
    $complexItemData = [
        'id' => 'ITEM_COMPLEX',
        'title' => 'Complex Item',
        'price' => 299.99,
        'description' => 'Long description here',
        'attributes' => [
            'color' => 'red',
            'size' => 'large',
        ],
        'images' => [
            ['url' => 'http://example.com/img1.jpg'],
            ['url' => 'http://example.com/img2.jpg'],
        ],
    ];

    $mockItemsClient = Mockery::mock(MeliItemsClient::class);
    $mockItemsClient->shouldReceive('getItem')
        ->andReturn($complexItemData);

    $mockRepository = Mockery::mock(ItemRepositoryInterface::class);
    $mockRepository->shouldReceive('saveFromApi')
        ->with($complexItemData)
        ->once();
    $mockRepository->shouldReceive('markAsProcessed')
        ->once();

    $message = new ProcessItemMessage('ITEM_COMPLEX', 'token');
    $job = new ProcessItemJob($message);

    $job->handle($mockItemsClient, $mockRepository);
});
