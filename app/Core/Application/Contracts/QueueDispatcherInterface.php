<?php

namespace App\Core\Application\Contracts;

interface QueueDispatcherInterface
{
    public function dispatch(object $command): void;
}
