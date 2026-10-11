<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Delay;

use Symfony\Component\Messenger\Stamp\DelayStamp;
use Trollbus\SymfonyMessengerBridge\Symfony\ConvertibleToSymfonyStamp;

final class Delay implements ConvertibleToSymfonyStamp
{
    /**
     * @param non-negative-int $milliseconds
     */
    public function __construct(
        public readonly int $milliseconds,
    ) {}

    /**
     * @param non-negative-int $seconds
     */
    public static function fromSeconds(int $seconds): self
    {
        return new self($seconds * 1000);
    }

    public function toSymfonyStamp(): DelayStamp
    {
        return new DelayStamp($this->milliseconds);
    }
}
