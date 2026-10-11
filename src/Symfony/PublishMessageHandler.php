<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Symfony;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Trollbus\SymfonyMessengerBridge\Consumer\Consumer;

final class PublishMessageHandler
{
    public function __construct(
        private readonly Consumer $consumer,
    ) {}

    #[AsMessageHandler]
    public function handle(PublishMessage $message): void
    {
        $this->consumer->consume($message->transport, $message->envelope);
    }
}
