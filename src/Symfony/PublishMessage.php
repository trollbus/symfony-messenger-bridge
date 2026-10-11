<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Symfony;

use Trollbus\MessageBus\Envelope;

final class PublishMessage
{
    public function __construct(
        public readonly Envelope $envelope,
        public readonly string $transport,
    ) {}
}
