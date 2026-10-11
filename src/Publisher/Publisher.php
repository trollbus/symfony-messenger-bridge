<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Publisher;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Trollbus\Message\Message;
use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\Stamp;
use Trollbus\SymfonyMessengerBridge\Symfony\ConvertibleToSymfonyStamp;
use Trollbus\SymfonyMessengerBridge\Symfony\PublishMessage;

final class Publisher
{
    /** @var array<non-empty-string, array<string, Envelope<void, Message<void>>>> */
    private array $queue = [];

    public function __construct(
        private readonly MessageBusInterface $symfonyMessageBus,
    ) {}

    /**
     * @param Envelope<void, Message<void>> $envelope
     */
    public function publish(string $transport, Envelope $envelope): void
    {
        $this->queue[$envelope->getMessageId()][$transport] = $envelope;
    }

    public function flush(): void
    {
        try {
            foreach ($this->queue as $transports) {
                foreach ($transports as $transport => $envelope) {
                    $this->symfonyMessageBus->dispatch(
                        new PublishMessage($envelope, $transport),
                        [
                            new TransportNamesStamp($transport),
                            ...array_map(
                                static fn(ConvertibleToSymfonyStamp $s) => $s->toSymfonyStamp(),
                                array_filter(
                                    $envelope->stamps,
                                    static fn(Stamp $s) => $s instanceof ConvertibleToSymfonyStamp,
                                ),
                            ),
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
