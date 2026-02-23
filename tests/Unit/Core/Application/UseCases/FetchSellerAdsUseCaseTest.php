<?php

declare(strict_types=1);

use App\Core\Application\Contracts\QueueDispatcherInterface;
use App\Core\Application\Messages\ProcessItemMessage;
use App\Core\Application\UseCases\FetchSellerAdsUseCase;
use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;

function createResultsWithIds(int $start, int $count): array
{
    return collect(range($start, $start + $count - 1))
        ->map(fn ($i) => ['id' => "ID_$i"])
        ->toArray();
}

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

it('increments offset correctly for pagination', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);

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
            ['results' => []]
        );

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);

    $dispatcher->shouldReceive('dispatch')
        ->times(3)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 10);
});

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

    $dispatcher->shouldReceive('dispatch')
        ->times(3)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 100);
});

it('stops exactly at the specified limit', function () {
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
        ->andReturn(['results' => createResultsWithIds(1, 7)]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);

    $dispatcher->shouldReceive('dispatch')
        ->times(7)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 7);
});

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

it('handles multiple pagination pages correctly', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);

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

it('stops iteration when limit is smaller than available results', function () {
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
        ->andReturn(['results' => createResultsWithIds(1, 10)]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);

    $dispatcher->shouldReceive('dispatch')
        ->times(7)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 7);
});

it('uses default max ads of 30 when not specified', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);

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

    $dispatcher->shouldReceive('dispatch')
        ->times(30)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392');
});

it('correctly handles mixed valid and invalid items across pages', function () {
    $authClient = Mockery::mock(MeliAuthClient::class);
    $authClient->shouldReceive('getToken')
        ->once()
        ->andReturn([
            'inactive_token' => 0,
            'access_token' => 'fake-token',
        ]);

    $searchClient = Mockery::mock(MeliSearchClient::class);

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
            ['results' => []]
        );

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);

    $dispatcher->shouldReceive('dispatch')
        ->times(7)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 10);
});
