<?php

declare(strict_types=1);

use App\Core\Infrastructure\Http\Clients\MeliAuthClient;

it('can request token from meli auth mock', function () {
    /** @var MeliAuthClient $client */
    $client = app(MeliAuthClient::class);

    $data = $client->getToken();

    expect($data)->not()->toBeNull()
        ->and($data)->toBeArray()
        ->and($data)->toHaveKeys([
            'inactive_token',
        ]);

    if (($data['inactive_token'] ?? 1) === 0) {
        expect($data)->toHaveKeys([
            'access_token',
            'store_id',
            'user_id',
        ]);
    }
});
