<?php

namespace App\Core\Infrastructure\Queue;

use App\Core\Application\Contracts\QueueDispatcherInterface;
use Illuminate\Support\Facades\Bus;

class LaravelQueueDispatcher implements QueueDispatcherInterface
{
    public function dispatch(object $command): void
    {
        Bus::dispatch($command);
    }
}
