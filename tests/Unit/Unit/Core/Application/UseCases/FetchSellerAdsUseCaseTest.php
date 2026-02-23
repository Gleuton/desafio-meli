<?php

declare(strict_types=1);

use App\Core\Application\Contracts\QueueDispatcherInterface;
use App\Core\Application\Messages\ProcessItemMessage;
use App\Core\Application\UseCases\FetchSellerAdsUseCase;
use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;

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
                'results' => collect(range(1, 5))->map(fn ($i) => ['id' => "ID_$i"])->toArray(),
            ],
            [
                'results' => collect(range(6, 10))->map(fn ($i) => ['id' => "ID_$i"])->toArray(),
            ]
        );

    $dispatcher = Mockery::mock(QueueDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->times(10)
        ->with(Mockery::type(ProcessItemMessage::class));

    $useCase = new FetchSellerAdsUseCase($authClient, $searchClient, $dispatcher);

    $useCase->execute('252254392', 10);
});
