<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Consumer;

use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

final class ConsumerTransportHandler implements Handler
{
    public function __construct(
        private readonly Handler $handler,
        private readonly string $transport,
    ) {}

    public function id(): string
    {
        return $this->handler->id();
    }

    public function handle(MessageContext $messageContext): mixed
    {
        if ($messageContext->getAttribute(ConsumeFromTransport::class)?->transport === $this->transport) {
            $this->handler->handle($messageContext);
        }

        return null;
    }
}
