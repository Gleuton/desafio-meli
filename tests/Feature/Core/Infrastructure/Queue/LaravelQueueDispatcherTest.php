<?php

declare(strict_types=1);

use App\Core\Application\Messages\ProcessItemMessage;
use App\Core\Infrastructure\Queue\LaravelQueueDispatcher;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Bus::fake();
});

it('dispatches command via bus', function () {
    $dispatcher = new LaravelQueueDispatcher;
    $command = new ProcessItemMessage('ITEM_123', 'token-123');

    $dispatcher->dispatch($command);

    Bus::assertDispatched(ProcessItemMessage::class);
});

it('dispatches multiple commands correctly', function () {
    $dispatcher = new LaravelQueueDispatcher;

    $command1 = new ProcessItemMessage('ITEM_1', 'token-1');
    $command2 = new ProcessItemMessage('ITEM_2', 'token-2');

    $dispatcher->dispatch($command1);
    $dispatcher->dispatch($command2);

    Bus::assertDispatchedTimes(ProcessItemMessage::class, 2);
});

it('passes command with correct properties to bus', function () {
    $dispatcher = new LaravelQueueDispatcher;
    $itemId = 'ITEM_SPECIFIC_999';
    $token = 'token-specific-999';

    $command = new ProcessItemMessage($itemId, $token);
    $dispatcher->dispatch($command);

    Bus::assertDispatched(static function (ProcessItemMessage $dispatched) use ($itemId, $token) {
        return $dispatched->itemId === $itemId && $dispatched->accessToken === $token;
    });
});
