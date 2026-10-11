<?php

declare(strict_types=1);

namespace Trollbus\SymfonyMessengerBridge\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Trollbus\Message\Event;
use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler\EventHandler;
use Trollbus\SymfonyMessengerBridge\Attribute\Async;
use Trollbus\SymfonyMessengerBridge\Consumer\ConsumerTransportHandler;
use Trollbus\SymfonyMessengerBridge\Publisher\Publisher;
use Trollbus\SymfonyMessengerBridge\Publisher\PublisherHandler;
use Trollbus\TrollbusBundle\DependencyInjection\MessageBusConfiguration;
use Trollbus\TrollbusBundle\HandlerRegistry\ContainerHandlerRegistry;

final class AsyncHandlerPass implements CompilerPassInterface
{
    public const CONSUMER_HANDLER_REGISTRY = 'trollbus.symfony_messenger_bridge.cosumer_handler_registry';
    public const CONSUMER_TAG = 'trollbus.symfony_messenger_bridge.consumer';

    public function process(ContainerBuilder $container): void
    {
        // Replace async handlers to PublisherHandler
        /**
         * @var list<array> $tags
         */
        foreach ($container->findTaggedServiceIds(MessageBusConfiguration::HANDLER_TAG) as $serviceId => $tags) {
            if ([] === $tags) {
                continue;
            }

            $handlerClass = self::stringOrNull($tags[0][MessageBusConfiguration::HANDLER_TAG_CLASS] ?? null);
            $handlerMethod = self::stringOrNull($tags[0][MessageBusConfiguration::HANDLER_TAG_METHOD] ?? null);

            if (null === $handlerClass || null === $handlerMethod) {
                continue;
            }

            // Read Async attribute from handler
            $refAsync = (new \ReflectionMethod($handlerClass, $handlerMethod))->getAttributes(Async::class);

            if (!isset($refAsync[0])) {
                continue;
            }

            $async = $refAsync[0]->newInstance();

            $handler = $container->getDefinition($serviceId);
            $handler->clearTag(MessageBusConfiguration::HANDLER_TAG);

            $publisher = $container->register($serviceId . '.symfony_messenger_bridge.publisher', PublisherHandler::class)
                ->setArgument('$inner', new Reference($serviceId))
                ->setArgument('$publisher', new Reference(Publisher::class))
                ->setArgument('$transport', $async->transport);

            $consumer = $container->register($serviceId . '.symfony_messenger_bridge.consumer', ConsumerTransportHandler::class)
                ->setArgument('$handler', $handler)
                ->setArgument('$transport', $async->transport);

            foreach ($tags as $tag) {
                $publisher->addTag(MessageBusConfiguration::HANDLER_TAG, $tag);
                $consumer->addTag(self::CONSUMER_TAG, $tag);
            }
        }

        // Initialize Consumer HandlerRegistry

        /** @var array<class-string<Message<mixed>>, Reference> $messageToHandlerMap */
        $messageToHandlerMap = [];

        foreach ($this->getMessageToHandlerIdsMap($container) as $message => $ids) {
            if (1 === \count($ids)) {
                $messageToHandlerMap[$message] = new Reference($ids[0]);

                continue;
            }

            if (!is_subclass_of($message, Event::class)) {
                throw new LogicException(\sprintf('Non-event message %s must have 1 handler, got %s', $message, \count($ids)));
            }

            $id = MessageBusConfiguration::nextHandlerId();
            $container->setDefinition($id, new Definition(EventHandler::class, [array_map(static fn($handlerId) => new Reference($handlerId), $ids)]));
            $messageToHandlerMap[$message] = new Reference($id);
        }

        $container->setDefinition(
            self::CONSUMER_HANDLER_REGISTRY,
            new Definition(
                ContainerHandlerRegistry::class,
                [
                    ServiceLocatorTagPass::register($container, $messageToHandlerMap),
                ],
            ),
        );
    }

    /**
     * @return array<class-string<Message<mixed>>, list<string>>
     */
    private function getMessageToHandlerIdsMap(ContainerBuilder $container): array
    {
        /** @var array<class-string<Message<mixed>>, list<string>> $messageToHandlerIdsMap */
        $messageToHandlerIdsMap = [];

        /**
         * @var list<array> $tags
         */
        foreach ($container->findTaggedServiceIds(self::CONSUMER_TAG) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $messageClass = self::stringOrNull($tag[MessageBusConfiguration::HANDLER_TAG_MESSAGE] ?? null);

                if (null === $messageClass || !is_subclass_of($messageClass, Message::class, true)) {
                    continue;
                }

                $messageToHandlerIdsMap[$messageClass][] = $serviceId;
                /** @phpstan-ignore nullCoalesce.offset */
                $messageToHandlerIdsMap[$messageClass] = array_values(array_unique($messageToHandlerIdsMap[$messageClass] ?? []));
            }
        }

        return $messageToHandlerIdsMap;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return \is_string($value) ? $value : null;
    }
}
