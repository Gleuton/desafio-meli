<?php

use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

test('getToken builds url correctly and returns response data on success', function () {
    $baseUrl = 'https://api.test/';
    $sellerId = 'seller-1';

    $expectedUrl = 'https://api.test/traymeli/sellers/seller-1';
    $payload = [
        'store_id' => 'store-1',
        'user_id' => 123,
        'access_token' => 'token-abc',
        'inactive_token' => 0,
    ];

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->with($expectedUrl, [])
        ->willReturn($payload);

    $client = new MeliAuthClient($httpClient, $baseUrl, $sellerId);

    expect($client->getToken())->toMatchArray($payload);
});

test('getToken urlencodes seller id', function () {
    $baseUrl = 'https://api.test';
    $sellerId = 'seller id/1';

    $expectedUrl = 'https://api.test/traymeli/sellers/seller+id%2F1';
    $payload = [
        'store_id' => 'store-1',
        'user_id' => 123,
        'access_token' => 'token-abc',
        'inactive_token' => 0,
    ];

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->with($expectedUrl, [])
        ->willReturn($payload);

    $client = new MeliAuthClient($httpClient, $baseUrl, $sellerId);

    expect($client->getToken())->toMatchArray($payload);
});

test('getToken bubbles up guzzle exceptions', function () {
    $baseUrl = 'https://api.test';
    $sellerId = 'seller-1';

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->willThrowException(new ConnectException('boom', new Request('GET', 'https://api.test')));

    $client = new MeliAuthClient($httpClient, $baseUrl, $sellerId);

    $client->getToken();
})->throws(ConnectException::class);

test('getToken logs rate limit (429) and re-throws exception', function () {
    Log::spy();

    $baseUrl = 'https://api.test';
    $sellerId = 'seller-1';

    $request = new Request('GET', 'https://api.test/traymeli/sellers/seller-1');
    $response = new Response(429, [], '{"message": "rate limit exceeded"}');
    $exception = new ClientException('Rate limit', $request, $response);

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->willThrowException($exception);

    $client = new MeliAuthClient($httpClient, $baseUrl, $sellerId);

    try {
        $client->getToken();
        $this->fail('Expected ClientException to be thrown');
    } catch (ClientException $e) {
        // Exception should be re-thrown
        expect($e)->toBe($exception);
    }

    // Verify that rate limit was logged
    Log::shouldHaveReceived('warning')
        ->once()
        ->with('[MeliAuthClient] Rate limit detected (429)', \Mockery::on(function ($context) use ($sellerId) {
            return isset($context['url'])
                && isset($context['seller_id'])
                && $context['seller_id'] === $sellerId
                && isset($context['response_body']);
        }));
});
