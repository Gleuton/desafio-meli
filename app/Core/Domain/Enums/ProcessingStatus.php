<?php

namespace App\Core\Domain\Enums;

enum ProcessingStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
}
