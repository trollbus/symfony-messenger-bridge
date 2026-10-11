<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Messenger\MessageBusInterface as SymfonyMessageBus;
use Trollbus\MessageBus\MessageBus;
use Trollbus\SymfonyMessengerBridge\Consumer\Consumer;
use Trollbus\SymfonyMessengerBridge\DependencyInjection\AsyncHandlerPass;
use Trollbus\SymfonyMessengerBridge\Publisher\Publisher;
use Trollbus\SymfonyMessengerBridge\Publisher\PublisherFlushMiddleware;
use Trollbus\SymfonyMessengerBridge\Symfony\PublishMessageHandler;
use Trollbus\TrollbusBundle\DependencyInjection\MessageBusConfiguration;

return static function (ContainerConfigurator $di): void {
    // Configure Publishers
    $di->services()
        ->set(Publisher::class)
            ->args([
                service(SymfonyMessageBus::class),
            ])
        ->set(PublisherFlushMiddleware::class)
            ->args([
                service(Publisher::class),
            ])
            ->tag(MessageBusConfiguration::MIDDLEWARE_TAG, ['priority' => -100]);

    // Configure Consumers
    $di->services()
        ->set(Consumer::class)
            ->args([
                service(MessageBus::class),
                service(AsyncHandlerPass::CONSUMER_HANDLER_REGISTRY),
                tagged_iterator(MessageBusConfiguration::MIDDLEWARE_TAG),
            ]);

    // Configure Symfony Messenger
    $di->services()
        ->set(PublishMessageHandler::class)
            ->args([
                service(Consumer::class),
            ])
            ->autoconfigure();
};
