<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Publisher;

use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

final class PublisherHandler implements Handler
{
    public function __construct(
        private readonly Handler $inner,
        private readonly Publisher $publisher,
        private readonly string $transport,
    ) {}

    public function id(): string
    {
        return $this->inner->id();
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $this->publisher->publish($this->transport, $messageContext->getEnvelop());

        return null;
    }
}
