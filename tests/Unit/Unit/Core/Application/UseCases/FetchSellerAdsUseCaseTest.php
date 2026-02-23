<?php

declare(strict_types=1);

use App\Core\Application\Contracts\QueueDispatcherInterface;
use App\Core\Application\Messages\ProcessItemMessage;
use App\Core\Application\UseCases\FetchSellerAdsUseCase;
use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;

// Helper functions
function createResultsWithIds(int $start, int $count): array
{
    return collect(range($start, $start + $count - 1))
        ->map(fn ($i) => ['id' => "ID_$i"])
        ->toArray();
}

// ===== Test: Original Test - Dispatches messages respecting limit =====
it('dispatches messages respecting limit', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    $searchClient->shouldReceive('searchBySeller')
        ->twice()
        ->andReturn(
            [
                'results' => createResultsWithIds(1, 5),
            ],
            [
                'results' => createResultsWithIds(6, 5),
            ]
        );

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->times(10)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 10);
});

// ===== Test: Inactive Token =====
it('returns early when token is inactive', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 1,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    $searchClient->shouldNotReceive('searchBySeller');

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    $dispatcher->shouldNotReceive('dispatch');

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 10);
});

// ===== Test: Pagination Offset Increments Correctly =====
it('increments offset correctly for pagination', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    // Expect calls with offset 0, 5, and 10
    $searchClient->shouldReceive('searchBySeller')
        ->with('252254392', 'fake-token', 5, 0)->once()
        ->andReturn(['results' => createResultsWithIds(1, 5)]);
    $searchClient->shouldReceive('searchBySeller')
        ->with('252254392', 'fake-token', 5, 5)->once()
        ->andReturn(['results' => createResultsWithIds(6, 5)]);
    $searchClient->shouldReceive('searchBySeller')
        ->with('252254392', 'fake-token', 5, 10)->once()
        ->andReturn(['results' => createResultsWithIds(11, 5)]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->times(15)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 15);
});

// ===== Test: Items Without ID Are Ignored =====
it('ignores items without id and does not dispatch them', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    $searchClient->shouldReceive('searchBySeller')
        ->times(2)
        ->andReturn(
            [
                'results' => [
                    ['id' => 'ID_1'],
                    ['name' => 'Item without ID'],
                    ['id' => 'ID_2'],
                    ['price' => 100],
                    ['id' => 'ID_3'],
                ],
            ],
            ['results' => []] // Empty response on second call
        );

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    // Should only dispatch 3 items (those with ID)
    $dispatcher->shouldReceive('dispatch')
        ->times(3)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 10);
});

// ===== Test: Empty Results Stop Pagination =====
it('stops pagination when results are empty', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andReturn(['results' => createResultsWithIds(1, 3)]);
    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andReturn(['results' => []]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    // Should only dispatch 3 items from first page
    $dispatcher->shouldReceive('dispatch')
        ->times(3)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 100); // Request many items
});

// ===== Test: Exact Limit Reached =====
it('stops exactly at the specified limit', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    // Should only be called once since first response will reach the limit
    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andReturn(['results' => createResultsWithIds(1, 7)]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    // Should dispatch exactly 7 items and no more
    $dispatcher->shouldReceive('dispatch')
        ->times(7)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 7);
});

// ===== Test: Message Contains Correct Data =====
it('dispatches ProcessItemMessage with correct item id and token', function () {
    $sellerId = '252254392';
    $token = 'test-access-token-123';

    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => $token,
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andReturn([
            'results' => [
                ['id' => 'ITEM_ABC_123'],
                ['id' => 'ITEM_XYZ_789'],
            ],
        ]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->with(Mockery::on(function (ProcessItemMessage $message) use ($token) {
            return $message->accessToken === $token
                && in_array($message->itemId, ['ITEM_ABC_123', 'ITEM_XYZ_789']);
        }))
        ->twice();

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute($sellerId, 2);
});

// ===== Test: Multiple Pagination Pages =====
it('handles multiple pagination pages correctly', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    // Simulate 4 pages with 5 items each
    $searchClient->shouldReceive('searchBySeller')
        ->andReturn(
            ['results' => createResultsWithIds(1, 5)],
            ['results' => createResultsWithIds(6, 5)],
            ['results' => createResultsWithIds(11, 5)],
            ['results' => createResultsWithIds(16, 5)]
        );

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->times(20)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 20);
});

// ===== Test: Limit Smaller Than Available Results =====
it('stops iteration when limit is smaller than available results', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    // First page has 10 items, but we only want 7
    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andReturn(['results' => createResultsWithIds(1, 10)]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    // Should dispatch exactly 7 items, not all 10
    $dispatcher->shouldReceive('dispatch')
        ->times(7)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 7);
});

// ===== Test: Default Max Ads =====
it('uses default max ads of 30 when not specified', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    // Simulate enough pages to reach 30 items
    $searchClient->shouldReceive('searchBySeller')
        ->andReturn(
            ['results' => createResultsWithIds(1, 5)],
            ['results' => createResultsWithIds(6, 5)],
            ['results' => createResultsWithIds(11, 5)],
            ['results' => createResultsWithIds(16, 5)],
            ['results' => createResultsWithIds(21, 5)],
            ['results' => createResultsWithIds(26, 5)]
        );

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    // Should dispatch exactly 30 items
    $dispatcher->shouldReceive('dispatch')
        ->times(30)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392'); // No max ads specified
});

// ===== Test: Mixed Valid and Invalid Items =====
it('correctly handles mixed valid and invalid items across pages', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);
    // First page: 3 valid items and 2 without ID
    // Second page: 4 valid items (total 7 valid items)
    $searchClient->shouldReceive('searchBySeller')
        ->times(3)
        ->andReturn(
            [
                'results' => [
                    ['id' => 'ID_1'],
                    ['name' => 'No ID'],
                    ['id' => 'ID_2'],
                    ['price' => 100],
                    ['id' => 'ID_3'],
                ],
            ],
            [
                'results' => createResultsWithIds(4, 4),
            ],
            ['results' => []] // Empty on third call to stop pagination
        );

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    // Should dispatch 7 valid items total (3 from first page + 4 from second page)
    $dispatcher->shouldReceive('dispatch')
        ->times(7)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 10);
});
