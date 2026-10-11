<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Consumer;

use Trollbus\MessageBus\ContextAttribute;

final class ConsumeFromTransport implements ContextAttribute
{
    public function __construct(
        public readonly string $transport,
    ) {}
}
