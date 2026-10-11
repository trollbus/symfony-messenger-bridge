<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Publisher;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class PublisherFlushMiddleware implements Middleware
{
    public function __construct(
        private readonly Publisher $publisher,
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        try {
            $result = $pipeline->continue();

            if (null === $messageContext->parent) {
                $this->publisher->flush();
            }

            return $result;
        } catch (\Throwable $e) {
            $this->publisher->reset();

            throw $e;
        }
    }
}
