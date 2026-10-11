<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Publisher;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @template TMessage of Message<void>
 *
 * @implements Handler<void, TMessage>
 */
final class PublisherHandler implements Handler
{
    /**
     * @param Handler<void, TMessage> $inner
     */
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
