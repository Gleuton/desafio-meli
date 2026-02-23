<?php

use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function meliAuthClientBaseConfig(): array
{
    return [
        'services.meli.base_url' => 'https://api.test/',
        'services.meli.seller_id' => 'seller-1',
    ];
}

test('getToken returns response data on success', function () {
    config(meliAuthClientBaseConfig());

    $url = 'https://api.test/traymeli/sellers/seller-1';
    $payload = [
        'store_id' => 'store-1',
        'user_id' => 123,
        'access_token' => 'token-abc',
        'inactive_token' => 0,
    ];

    Http::fake([
        $url => Http::response($payload, 200),
    ]);

    $client = new MeliAuthClient;

    expect($client->getToken())->toMatchArray($payload);

    Http::assertSent(function ($request) use ($url) {
        return $request->url() === $url && $request->method() === 'GET';
    });
});

test('getToken returns null on rate limit', function () {
    config(meliAuthClientBaseConfig());

    $url = 'https://api.test/traymeli/sellers/seller-1';

    Http::fake([
        $url => Http::response(['message' => 'rate limit'], 429),
    ]);

    $client = new MeliAuthClient;

    expect($client->getToken())->toBeNull();
});

test('getToken returns null on non successful response', function () {
    config(meliAuthClientBaseConfig());

    $url = 'https://api.test/traymeli/sellers/seller-1';

    Http::fake([
        $url => Http::response('server error', 500),
    ]);

    $client = new MeliAuthClient;

    expect($client->getToken())->toBeNull();
});

test('getToken returns null on exception', function () {
    config(meliAuthClientBaseConfig());

    Http::fake(function () {
        throw new Exception('boom');
    });

    $client = new MeliAuthClient;

    expect($client->getToken())->toBeNull();
});
