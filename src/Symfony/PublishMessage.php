<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Symfony;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Envelope;

final class PublishMessage
{
    /**
     * @param Envelope<void, Message<void>> $envelope
     */
    public function __construct(
        public readonly Envelope $envelope,
        public readonly string $transport,
    ) {}
}
