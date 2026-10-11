<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Symfony;

use Symfony\Component\Messenger\Stamp\StampInterface;
use Trollbus\MessageBus\Stamp;

interface ConvertibleToSymfonyStamp extends Stamp
{
    public function toSymfonyStamp(): StampInterface;
}
