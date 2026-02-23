<?php

declare(strict_types=1);

use App\Core\Infrastructure\Http\GuzzleHttpClient;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

function createMockStream(string $content): StreamInterface
{
    $stream = Mockery::mock(StreamInterface::class);
    $stream->shouldReceive('__toString')
        ->andReturn($content);

    return $stream;
}

it('returns parsed json response on success', function () {
    $responseData = [
        'status' => 'success',
        'data' => ['id' => 123, 'name' => 'Test Item'],
    ];

    $mockResponse = Mockery::mock(ResponseInterface::class);
    $mockResponse->shouldReceive('getBody')
        ->andReturn(createMockStream(json_encode($responseData)));

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')
        ->with('https://api.example.com/endpoint', ['Authorization' => 'Bearer token'])
        ->andReturn($mockResponse);

    $httpClient = new GuzzleHttpClient($mockClient);
    $result = $httpClient->get('https://api.example.com/endpoint', ['Authorization' => 'Bearer token']);

    expect($result)->toBe($responseData);
});

it('throws GuzzleException when request fails', function () {
    $exception = new RuntimeException('Connection error');

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')
        ->andThrow($exception);

    $httpClient = new GuzzleHttpClient($mockClient);

    expect(static fn () => $httpClient->get('https://api.example.com/not-found', []))
        ->toThrow(RuntimeException::class);
});

it('throws JsonException on invalid json response', function () {
    $mockResponse = Mockery::mock(ResponseInterface::class);
    $mockResponse->shouldReceive('getBody')
        ->andReturn(createMockStream('invalid json {'));

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')
        ->andReturn($mockResponse);

    $httpClient = new GuzzleHttpClient($mockClient);

    expect(fn () => $httpClient->get('https://api.example.com/endpoint', []))
        ->toThrow(JsonException::class);
});

it('passes options to guzzle client', function () {
    $options = [
        'headers' => ['Authorization' => 'Bearer token'],
        'timeout' => 30,
    ];

    $responseData = ['success' => true];

    $mockResponse = Mockery::mock(ResponseInterface::class);
    $mockResponse->shouldReceive('getBody')
        ->andReturn(createMockStream(json_encode($responseData)));

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')
        ->with('https://api.example.com/test', $options)
        ->andReturn($mockResponse);

    $httpClient = new GuzzleHttpClient($mockClient);
    $result = $httpClient->get('https://api.example.com/test', $options);

    expect($result)->toBe($responseData);
});

it('handles empty json response', function () {
    $mockResponse = Mockery::mock(ResponseInterface::class);
    $mockResponse->shouldReceive('getBody')
        ->andReturn(createMockStream('{}'));

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')
        ->andReturn($mockResponse);

    $httpClient = new GuzzleHttpClient($mockClient);
    $result = $httpClient->get('https://api.example.com/empty', []);

    expect($result)->toBe([]);
});

it('handles json arrays response', function () {
    $responseData = [
        ['id' => 1, 'name' => 'Item 1'],
        ['id' => 2, 'name' => 'Item 2'],
    ];

    $mockResponse = Mockery::mock(ResponseInterface::class);
    $mockResponse->shouldReceive('getBody')
        ->andReturn(createMockStream(json_encode($responseData)));

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')
        ->andReturn($mockResponse);

    $httpClient = new GuzzleHttpClient($mockClient);
    $result = $httpClient->get('https://api.example.com/items', []);

    expect($result)->toBe($responseData);
});

it('handles complex nested json structures', function () {
    $responseData = [
        'results' => [
            [
                'id' => 'ID_1',
                'metadata' => [
                    'created_at' => '2026-02-23',
                    'tags' => ['tag1', 'tag2'],
                ],
            ],
        ],
    ];

    $mockResponse = Mockery::mock(ResponseInterface::class);
    $mockResponse->shouldReceive('getBody')
        ->andReturn(createMockStream(json_encode($responseData)));

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')
        ->andReturn($mockResponse);

    $httpClient = new GuzzleHttpClient($mockClient);
    $result = $httpClient->get('https://api.example.com/complex', []);

    expect($result)->toBe($responseData)
        ->and($result['results'][0]['metadata']['tags'][0])->toBe('tag1');
});
