<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Consumer;

use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\HandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class Consumer
{
    /**
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        private readonly MessageBus $bus,
        private readonly HandlerRegistry $handlerRegistry,
        private readonly iterable $middlewares,
    ) {}

    public function consume(string $transport, Envelope $envelope): void
    {
        $handler = $this->handlerRegistry->get($envelope->getMessageClass());
        $messageContext = $this->bus->startContext($envelope);
        $messageContext->addAttributes(new ConsumeFromTransport($transport));

        Pipeline::handle(
            messageContext: $messageContext,
            handler: $handler,
            middlewares: $this->middlewares,
        );
    }
}
