<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Publisher;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Trollbus\MessageBus\Envelope;
use Trollbus\SymfonyMessengerBridge\Symfony\PublishMessage;

final class Publisher
{
    /** @var list<Envelope> */
    private array $queue = [];

    public function __construct(
        private readonly MessageBusInterface $symfonyMessageBus,
    ) {}

    public function publish(string $transport, Envelope $envelope): void
    {
        $this->queue[$envelope->getMessageId()][$transport] = $envelope;
    }

    public function flush(): void
    {
        try {
            /**
             * @var Envelope $envelope
             * @var non-empty-list<string> $transports
             */
            foreach ($this->queue as $messageId => $transports) {
                foreach ($transports as $transport => $envelope) {
                    $this->symfonyMessageBus->dispatch(
                        new PublishMessage($envelope, $transport),
                        [
                            new TransportNamesStamp($transport),
                        ],
                    );
                }
            }
        } finally {
            $this->queue = [];
        }
    }

    public function reset(): void
    {
        $this->queue = [];
    }
}
