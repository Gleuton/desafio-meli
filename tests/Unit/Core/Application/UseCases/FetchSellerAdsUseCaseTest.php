<?php

declare(strict_types=1);

use App\Core\Application\Contracts\LoggerInterface;
use App\Core\Application\Contracts\QueueDispatcherInterface;
use App\Core\Application\Exceptions\FailedToFetchAdsException;
use App\Core\Application\UseCases\FetchSellerAdsUseCase;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use App\Jobs\ProcessItemJob;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;

function createResultsWithIds(int $start, int $count): array
{
    return collect(range($start, $start + $count - 1))
        ->map(fn ($i) => "ID_$i")
        ->toArray();
}

function createLoggerMock(): LoggerInterface
{
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')
        ->zeroOrMoreTimes();
    $logger->shouldReceive('debug')
        ->zeroOrMoreTimes();
    $logger->shouldReceive('warning')
        ->zeroOrMoreTimes();
    $logger->shouldReceive('error')
        ->zeroOrMoreTimes();

    return $logger;
}

function createRepositoryMock(?int $expectedCreatePendingCalls = null): ItemRepositoryInterface
{
    $repository = Mockery::mock(ItemRepositoryInterface::class);

    if ($expectedCreatePendingCalls !== null) {
        $repository->shouldReceive('createPending')
            ->times($expectedCreatePendingCalls)
            ->withArgs(function (string $itemId, ?string $sellerId = null) {
                return is_string($itemId);
            });

        return $repository;
    }

    $repository->shouldReceive('createPending')
        ->zeroOrMoreTimes()
        ->withArgs(function (string $itemId, ?string $sellerId = null) {
            return is_string($itemId);
        });

    return $repository;
}

it('dispatches messages respecting limit', function () {
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
        ->with(Mockery::type(ProcessItemJob::class));

    $repository = createRepositoryMock(10);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute('252254392', 'fake-token', 10);
});

it('increments offset correctly for pagination', function () {
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
        ->with(Mockery::type(ProcessItemJob::class));

    $repository = createRepositoryMock(15);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute('252254392', 'fake-token', 15);
});

it('ignores items without id and does not dispatch them', function () {
    $searchClient = Mockery::mock(MeliSearchClient::class);
    $searchClient->shouldReceive('searchBySeller')
        ->times(2)
        ->andReturn(
            [
                'results' => [
                    'ID_1',
                    '',
                    'ID_2',
                    123,
                    'ID_3',
                ],
            ],
            ['results' => []]
        );

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);

    $dispatcher->shouldReceive('dispatch')
        ->times(3)
        ->with(Mockery::type(ProcessItemJob::class));

    $repository = createRepositoryMock(3);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute('252254392', 'fake-token', 10);
});

it('stops pagination when results are empty', function () {
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
        ->with(Mockery::type(ProcessItemJob::class));

    $repository = createRepositoryMock(3);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute('252254392', 'fake-token', 100);
});

it('stops exactly at the specified limit', function () {
    $searchClient = Mockery::mock(MeliSearchClient::class);

    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andReturn(['results' => createResultsWithIds(1, 7)]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);

    $dispatcher->shouldReceive('dispatch')
        ->times(7)
        ->with(Mockery::type(ProcessItemJob::class));

    $repository = createRepositoryMock(7);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute('252254392', 'fake-token', 7);
});

it('dispatches ProcessItemMessage with correct item id and token', function () {
    $sellerId = '252254392';
    $token = 'test-access-token-123';

    $searchClient = Mockery::mock(MeliSearchClient::class);
    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andReturn([
            'results' => [
                'ITEM_ABC_123',
                'ITEM_XYZ_789',
            ],
        ]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->with(
            Mockery::on(function (ProcessItemJob $job) use ($token) {
                return $job->accessToken === $token
                    && in_array($job->itemId, ['ITEM_ABC_123', 'ITEM_XYZ_789']);
            })
        )
        ->twice();

    $repository = createRepositoryMock(2);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute($sellerId, $token, 2);
});

it('handles multiple pagination pages correctly', function () {
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
        ->with(Mockery::type(ProcessItemJob::class));

    $repository = createRepositoryMock(20);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute('252254392', 'fake-token', 20);
});

it('stops iteration when limit is smaller than available results', function () {
    $searchClient = Mockery::mock(MeliSearchClient::class);

    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andReturn(['results' => createResultsWithIds(1, 10)]);

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);

    $dispatcher->shouldReceive('dispatch')
        ->times(7)
        ->with(Mockery::type(ProcessItemJob::class));

    $repository = createRepositoryMock(7);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute('252254392', 'fake-token', 7);
});

it('uses default max ads of 30 when not specified', function () {
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
        ->with(Mockery::type(ProcessItemJob::class));

    $repository = createRepositoryMock(30);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute('252254392', 'fake-token');
});

it('correctly handles mixed valid and invalid items across pages', function () {
    $searchClient = Mockery::mock(MeliSearchClient::class);

    $searchClient->shouldReceive('searchBySeller')
        ->times(3)
        ->andReturn(
            [
                'results' => [
                    'ID_1',
                    '',
                    'ID_2',
                    null,
                    'ID_3',
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
        ->with(Mockery::type(ProcessItemJob::class));

    $repository = createRepositoryMock(7);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    $useCase->execute('252254392', 'fake-token', 10);
});

it('throws FailedToFetchAdsException when network timeout occurs', function () {
    $request = Mockery::mock(RequestInterface::class);
    $searchClient = Mockery::mock(MeliSearchClient::class);
    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andThrow(new ConnectException('Connection timeout', $request));

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    $repository = createRepositoryMock(0);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    expect(fn () => $useCase->execute('252254392', 'fake-token', 10))
        ->toThrow(FailedToFetchAdsException::class);
});

it('throws FailedToFetchAdsException when API request fails', function () {
    $request = Mockery::mock(RequestInterface::class);
    $searchClient = Mockery::mock(MeliSearchClient::class);
    $searchClient->shouldReceive('searchBySeller')
        ->once()
        ->andThrow(new RequestException('Invalid token', $request));

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    $repository = createRepositoryMock(0);
    $logger = createLoggerMock();

    $useCase = new FetchSellerAdsUseCase($searchClient, $dispatcher, $repository, $logger);

    expect(static fn () => $useCase->execute('252254392', 'fake-token', 10))
        ->toThrow(FailedToFetchAdsException::class);
});
