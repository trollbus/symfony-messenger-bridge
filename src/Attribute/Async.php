<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Async
{
    public function __construct(
        public readonly string $transport,
    ) {}
}
